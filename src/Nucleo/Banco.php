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

    private function __construct() {}

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
        // Prioridade: configurar() (testes, demo) > variável de ambiente DB_DSN > SQLite local
        $dsn = self::$dsn !== '' ? self::$dsn : (string) getenv('DB_DSN');
        if ($dsn === '') {
            $dsn = 'sqlite:' . dirname(__DIR__, 2) . '/data/estudo.sqlite';
        }
        $usuario = getenv('DB_USER');
        $senha = getenv('DB_PASSWORD');
        $usuario = is_string($usuario) && $usuario !== '' ? $usuario : null;
        $senha = is_string($senha) && $senha !== '' ? $senha : null;
        $this->pdo = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
        $this->criarEsquema();
    }

    /** Conexão aberta (abre se preciso); evita checar null em cada método. */
    private function pdo(): PDO
    {
        $this->abrirConexao();
        return $this->pdo ?? throw new NucleoException('Conexão não disponível.');
    }

    public function abrirTransacao(): void
    {
        $this->abrirConexao();
        if ($this->nivelTransacao === 0) {
            $this->pdo()->beginTransaction();
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
            $this->pdo()->commit();
        }
    }

    public function cancelarTransacao(): void
    {
        if ($this->nivelTransacao > 0 && $this->pdo()->inTransaction()) {
            $this->pdo()->rollBack();
        }
        $this->nivelTransacao = 0;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function consultarSql(string $sql, array $params = []): array
    {
        $st = $this->pdo()->prepare($sql);
        $st->execute($params);
        /** @var list<array<string, mixed>> $linhas FETCH_ASSOC configurado na conexão */
        $linhas = $st->fetchAll();
        return $linhas;
    }

    /** @param array<string, mixed> $params */
    public function executarSql(string $sql, array $params = []): int
    {
        $st = $this->pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public function ultimoId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    private function criarEsquema(): void
    {
        $this->pdo()->exec(<<<'SQL'
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
