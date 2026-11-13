<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

use PDO;

/**
 * Singleton de banco (MySQL via PDO). A conexão vem das variáveis de ambiente
 * (ver ConfiguracaoBanco); o esquema é criado pelas migrations, nunca em tempo de execução.
 * Transações aninhadas apenas "entram" na transação externa (simplificação).
 */
final class Banco
{
    private static ?Banco $instancia = null;

    private ?PDO $pdo = null;
    private int $nivelTransacao = 0;
    /** Quantas transações reais foram abertas (para demonstrar o problema do loop). */
    public int $totalTransacoes = 0;

    private function __construct() {}

    public static function getInstance(): self
    {
        return self::$instancia ??= new self();
    }

    public function abrirConexao(): void
    {
        if ($this->pdo !== null) {
            return;
        }
        $config = ConfiguracaoBanco::doAmbiente();
        $this->pdo = new PDO($config->dsn(), $config->usuario, $config->senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepared statements reais no servidor (não emulados pelo driver)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
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
}
