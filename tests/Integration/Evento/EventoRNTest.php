<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Evento;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;

final class EventoRNTest extends IntegrationTestCase
{
    #[Test]
    public function intimacaoCalculaPrazoFinal(): void
    {
        $id = $this->cadastrarProcesso();
        $evento = new EventoDTO();
        $evento->setNumIdProcesso($id)->setStrTipo('INTIMACAO')->setStrDescricao('Intimação para réplica')->setNumDiasPrazo(15);

        $evento = (new EventoRN())->cadastrar($evento);

        $this->assertNotNull($evento->getDtaPrazoFinal());
    }

    #[Test]
    public function citacaoSemPrazoEhRejeitada(): void
    {
        $id = $this->cadastrarProcesso();
        $evento = new EventoDTO();
        $evento->setNumIdProcesso($id)->setStrTipo('CITACAO')->setStrDescricao('Citação');

        $this->expectException(NucleoException::class);
        (new EventoRN())->cadastrar($evento);
    }

    #[Test]
    public function processoBaixadoNaoAdmiteEventos(): void
    {
        $id = $this->cadastrarProcesso();
        $baixa = new ProcessoDTO();
        $baixa->setNumIdProcesso($id)->setStrSituacao('B');
        (new ProcessoRN())->alterarSituacao($baixa);

        $evento = new EventoDTO();
        $evento->setNumIdProcesso($id)->setStrTipo('PETICAO')->setStrDescricao('Petição após baixa');

        $this->expectException(NucleoException::class);
        (new EventoRN())->cadastrar($evento);
    }
}
