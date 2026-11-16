<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

use Redis;

/** Cache no Redis (extensão phpredis). Os valores são gravados como JSON, com expiração (SET ... EX). */
final class RedisCache implements Cache
{
    public function __construct(private readonly Redis $redis) {}

    public static function conectar(ConfiguracaoRedis $config): self
    {
        $redis = new Redis();
        $redis->connect($config->host, $config->porta, 2.5);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, 2.5);
        return new self($redis);
    }

    public function buscar(string $chave): ?array
    {
        $json = $this->redis->get($chave);
        if (!is_string($json)) {
            return null;
        }
        $valor = json_decode($json, true);
        if (!is_array($valor)) {
            return null;
        }
        /** @var array<string, mixed> $valor */
        return $valor;
    }

    public function guardar(string $chave, array $valor, int $segundos): void
    {
        $this->redis->set($chave, json_encode($valor, JSON_THROW_ON_ERROR), ['EX' => $segundos]);
    }

    public function remover(string $chave): void
    {
        $this->redis->del($chave);
    }
}
