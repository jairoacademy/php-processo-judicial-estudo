<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

use PDO;

/**
 * Singleton de banco. Usa SQLite para rodar sem servidor.
 * Transações aninhadas apenas "entram" na transação externa (simplificação).
 */
final class Banco
{
    private static ?Banco $instancia = null;
    private static string $dsn = '';

    private ?PDO $pdo = null;
    private int $nivelTransacao = 0;
    /** Quantas transações reais foram abertas (para demonstrar o problema do loop). */
    public int $totalTransacoes = 0;

    private function __construct()
    {
    }

    public static function configurar(string $dsn): void
    {
        self::$dsn = $dsn;
        self::$instancia = null;
    }

    public static function getInstance(): self
    {
        return self::$instancia ??= new self();
    }

    public function abrirConexao(): void
    {
        if ($this->pdo !== null) {
            return;
        }
        $dsn = self::$dsn !== '' ? self::$dsn : 'sqlite:' . dirname(__DIR__, 2) . '/data/estudo.sqlite';
        $this->pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->criarEsquema();
    }

    public function abrirTransacao(): void
    {
        $this->abrirConexao();
        if ($this->nivelTransacao === 0) {
            $this->pdo->beginTransaction();
            $this->totalTransacoes++;
        }
        $this->nivelTransacao++;
    }

    public function confirmarTransacao(): void
    {
        if ($this->nivelTransacao === 0) {
            return;
        }
        $this->nivelTransacao--;
        if ($this->nivelTransacao === 0) {
            $this->pdo->commit();
        }
    }

    public function cancelarTransacao(): void
    {
        if ($this->nivelTransacao > 0 && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $this->nivelTransacao = 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function consultarSql(string $sql, array $params = []): array
    {
        $this->abrirConexao();
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function executarSql(string $sql, array $params = []): int
    {
        $this->abrirConexao();
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public function ultimoId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    private function criarEsquema(): void
    {
        $this->pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS processo (
  id_processo      INTEGER PRIMARY KEY AUTOINCREMENT,
  numero           TEXT NOT NULL UNIQUE,
  classe           TEXT NOT NULL,
  assunto          TEXT NOT NULL,
  orgao_julgador   TEXT NOT NULL,
  sta_situacao     TEXT NOT NULL DEFAULT 'A',
  sin_sigilo       TEXT NOT NULL DEFAULT 'N',
  dta_distribuicao TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS parte (
  id_parte     INTEGER PRIMARY KEY AUTOINCREMENT,
  id_processo  INTEGER NOT NULL REFERENCES processo(id_processo),
  nome         TEXT NOT NULL,
  documento    TEXT,
  sta_polo     TEXT NOT NULL,
  sta_tipo     TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS evento (
  id_evento       INTEGER PRIMARY KEY AUTOINCREMENT,
  id_processo     INTEGER NOT NULL REFERENCES processo(id_processo),
  sta_tipo        TEXT NOT NULL,
  descricao       TEXT NOT NULL,
  dth_evento      TEXT NOT NULL,
  usuario         TEXT NOT NULL,
  num_dias_prazo  INTEGER,
  dta_prazo_final TEXT
);
CREATE INDEX IF NOT EXISTS i01_evento_processo ON evento (id_processo, dth_evento);
CREATE INDEX IF NOT EXISTS i01_parte_processo ON parte (id_processo);
SQL);
    }
}
