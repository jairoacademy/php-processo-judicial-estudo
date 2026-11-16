<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Compartilhado\Cache;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Autuacao\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Autuacao\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Compartilhado\Cache\Caches;
use ProcessoJudicialEstudo\Compartilhado\Nucleo\Banco;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;

/** Cache-aside na consulta do processo por id, com invalidação quando a situação muda. */
final class CacheDoProcessoTest extends IntegrationTestCase
{
    #[Test]
    public function consultaPorIdGuardaOProcessoNoCache(): void
    {
        $id = $this->cadastrarProcesso();

        $this->consultarPorId($id);

        $guardado = Caches::atual()->buscar("processo:$id");
        $this->assertNotNull($guardado);
        $this->assertSame($id, $guardado['NumIdProcesso']);
    }

    #[Test]
    public function segundaConsultaVemDoCacheSemIrAoBanco(): void
    {
        $id = $this->cadastrarProcesso();
        $this->consultarPorId($id);

        // Muda o banco por fora da aplicação: se a consulta fosse ao banco, veria a classe nova
        Banco::getInstance()->executarSql('UPDATE processo SET classe = :c WHERE id_processo = :id', [':c' => 'Alterada por fora', ':id' => $id]);

        $this->assertSame('Procedimento Comum Cível', $this->consultarPorId($id)->getStrClasse());
    }

    #[Test]
    public function alterarSituacaoInvalidaOCache(): void
    {
        $id = $this->cadastrarProcesso();
        $this->consultarPorId($id);

        $baixa = new ProcessoDTO();
        $baixa->setNumIdProcesso($id)->setStrSituacao('B');
        new ProcessoRN()->alterarSituacao($baixa);

        $this->assertNull(Caches::atual()->buscar("processo:$id"));
        $this->assertSame('B', $this->consultarPorId($id)->getStrSituacao());
    }

    #[Test]
    public function consultaComOutrosFiltrosNaoUsaOCache(): void
    {
        $id = $this->cadastrarProcesso();
        $filtro = new ProcessoDTO();
        $filtro->setNumIdProcesso($id)->retStrNumero();

        new ProcessoRN()->consultar($filtro);

        $this->assertNull(Caches::atual()->buscar("processo:$id"));
    }

    private function consultarPorId(int $id): ProcessoDTO
    {
        $filtro = new ProcessoDTO();
        $filtro->setNumIdProcesso($id);
        $processo = new ProcessoRN()->consultar($filtro);
        $this->assertNotNull($processo);
        return $processo;
    }
}
