<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

/**
 * Parâmetros do Redis lidos do ambiente (REDIS_HOST, REDIS_PORT).
 * Sem REDIS_HOST, a aplicação roda sem cache e com a sessão em arquivo.
 */
final readonly class ConfiguracaoRedis
{
    public function __construct(
        public string $host,
        public int $porta,
    ) {}

    public static function doAmbiente(): ?self
    {
        $host = getenv('REDIS_HOST');
        if (!is_string($host) || $host === '') {
            return null;
        }
        $porta = getenv('REDIS_PORT');
        return new self($host, is_numeric($porta) ? (int) $porta : 6379);
    }

    /** Valor de session.save_path para guardar as sessões do PHP no Redis. */
    public function caminhoSessao(): string
    {
        return "tcp://{$this->host}:{$this->porta}?read_timeout=2.5&prefix=sessao:";
    }
}
