<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Processo;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;
use ProcessoJudicialEstudo\Util\Cnj;

final class ProcessoRNTest extends IntegrationTestCase
{
    #[Test]
    public function cadastrarDevolveChavePrimaria(): void
    {
        $p = new ProcessoRN()->cadastrar($this->novoProcessoDTO(Cnj::montar(1, 2026)));

        $this->assertSame(1, $p->getNumIdProcesso());
    }

    #[Test]
    public function numeroInvalidoGeraValidacao(): void
    {
        $e = $this->capturarNucleoException(fn() => new ProcessoRN()->cadastrar($this->novoProcessoDTO('123')));

        $this->assertTrue($e->contemValidacoes());
    }

    #[Test]
    public function numeroDuplicadoEhRejeitado(): void
    {
        $this->cadastrarProcesso(1);

        $this->expectException(NucleoException::class);
        new ProcessoRN()->cadastrar($this->novoProcessoDTO(Cnj::montar(1, 2026)));
    }

    #[Test]
    public function distribuicaoGeraEvento(): void
    {
        $id = $this->cadastrarProcesso();

        $filtro = new EventoDTO();
        $filtro->setNumIdProcesso($id);
        $this->assertCount(1, new EventoRN()->listar($filtro));
    }
}
