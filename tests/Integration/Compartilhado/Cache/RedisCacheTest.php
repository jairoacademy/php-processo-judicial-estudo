<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Compartilhado\Cache;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProcessoJudicialEstudo\Compartilhado\Cache\ConfiguracaoRedis;
use ProcessoJudicialEstudo\Compartilhado\Cache\RedisCache;

/** A RedisCache contra um Redis de verdade (no Docker e no CI). Sem REDIS_HOST, o teste é pulado. */
final class RedisCacheTest extends TestCase
{
    private RedisCache $cache;
    private string $chave;

    protected function setUp(): void
    {
        $config = ConfiguracaoRedis::doAmbiente();
        if ($config === null) {
            $this->markTestSkipped('REDIS_HOST não configurado.');
        }
        $this->cache = RedisCache::conectar($config);
        $this->chave = 'teste:' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->cache)) {
            $this->cache->remover($this->chave);
        }
    }

    #[Test]
    public function guardaEBuscaUmArray(): void
    {
        $this->cache->guardar($this->chave, ['NumIdProcesso' => 7, 'StrNumero' => '0000001-69.2026.8.19.0001'], 60);

        $this->assertSame(['NumIdProcesso' => 7, 'StrNumero' => '0000001-69.2026.8.19.0001'], $this->cache->buscar($this->chave));
    }

    #[Test]
    public function chaveInexistenteDevolveNull(): void
    {
        $this->assertNull($this->cache->buscar($this->chave));
    }

    #[Test]
    public function removerApagaAChave(): void
    {
        $this->cache->guardar($this->chave, ['a' => 1], 60);

        $this->cache->remover($this->chave);

        $this->assertNull($this->cache->buscar($this->chave));
    }

    #[Test]
    public function chaveExpiraDepoisDoPrazo(): void
    {
        $this->cache->guardar($this->chave, ['a' => 1], 1);

        sleep(2);

        $this->assertNull($this->cache->buscar($this->chave));
    }
}
