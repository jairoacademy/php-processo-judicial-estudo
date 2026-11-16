<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

/**
 * Ponto único de acesso ao cache da aplicação (mesma ideia do Banco::getInstance()).
 * Na primeira chamada escolhe a implementação pelo ambiente: Redis, se configurado; senão, SemCache.
 * Os testes trocam a implementação com usar().
 */
final class Caches
{
    private static ?Cache $atual = null;

    public static function atual(): Cache
    {
        return self::$atual ??= self::criarDoAmbiente();
    }

    public static function usar(Cache $cache): void
    {
        self::$atual = $cache;
    }

    private static function criarDoAmbiente(): Cache
    {
        $config = ConfiguracaoRedis::doAmbiente();
        if ($config === null) {
            return new SemCache();
        }
        try {
            return RedisCache::conectar($config);
        } catch (\RedisException $e) {
            // Redis fora do ar não derruba a aplicação: segue sem cache e registra no log
            error_log('Redis indisponível, seguindo sem cache: ' . $e->getMessage());
            return new SemCache();
        }
    }
}
