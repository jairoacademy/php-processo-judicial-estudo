<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

/**
 * Parâmetros de conexão lidos das variáveis de ambiente (DB_HOST, DB_PORT, DB_NAME,
 * DB_USER, DB_PASSWORD). Compartilhados pela aplicação (PDO) e pelas migrations (DBAL).
 */
final readonly class ConfiguracaoBanco
{
    public function __construct(
        public string $host,
        public int $porta,
        public string $banco,
        public string $usuario,
        public string $senha,
    ) {}

    public static function doAmbiente(): self
    {
        return new self(
            host: self::variavel('DB_HOST', 'mysql'),
            porta: (int) self::variavel('DB_PORT', '3306'),
            banco: self::variavel('DB_NAME', 'processo'),
            usuario: self::variavel('DB_USER', 'processo'),
            senha: self::variavel('DB_PASSWORD', ''),
        );
    }

    public function dsn(): string
    {
        return "mysql:host={$this->host};port={$this->porta};dbname={$this->banco};charset=utf8mb4";
    }

    private static function variavel(string $nome, string $padrao): string
    {
        $valor = getenv($nome);
        return is_string($valor) && $valor !== '' ? $valor : $padrao;
    }
}
