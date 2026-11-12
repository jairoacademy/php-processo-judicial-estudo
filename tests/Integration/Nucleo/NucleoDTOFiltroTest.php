<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Nucleo;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Nucleo\NucleoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;
use ProcessoJudicialEstudo\Util\Cnj;

/** O DTO funciona como construtor de consultas: set vira WHERE, setOrd vira ORDER BY, ret limita colunas. */
final class NucleoDTOFiltroTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cadastrarProcesso(1, '1ª Vara Cível');
        $this->cadastrarProcesso(3, '2ª Vara Cível');
        $this->cadastrarProcesso(4, '3ª Vara Cível');
    }

    #[Test]
    public function filtroInComOrdenacaoDecrescente(): void
    {
        $f = new ProcessoDTO();
        $f->setStrOrgaoJulgador(['2ª Vara Cível', '3ª Vara Cível'], NucleoDTO::OPER_IN)->setOrdStrNumero('DESC');

        $lista = new ProcessoRN()->listar($f);

        $this->assertCount(2, $lista);
        $this->assertSame(Cnj::montar(4, 2026), $lista[0]->getStrNumero());
    }

    #[Test]
    public function filtroLikeComPaginacao(): void
    {
        $f = new ProcessoDTO();
        $f->setStrOrgaoJulgador('Vara', NucleoDTO::OPER_LIKE)
            ->setNumMaxRegistrosRetorno(2)
            ->setNumPaginaAtual(1)
            ->setOrdNumIdProcesso();

        $this->assertCount(1, new ProcessoRN()->listar($f));
    }

    #[Test]
    public function retLimitaAsColunasRetornadas(): void
    {
        $numero = Cnj::montar(1, 2026);
        $f = new ProcessoDTO();
        $f->setStrNumero($numero)->retStrNumero()->retStrClasse();

        $p = new ProcessoRN()->consultar($f);

        $this->assertNotNull($p);
        $this->assertSame($numero, $p->getStrNumero());
        $this->assertNull($p->getStrAssunto());
    }
}
