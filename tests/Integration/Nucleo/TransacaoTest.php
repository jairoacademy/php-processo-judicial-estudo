<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Nucleo;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;
use ProcessoJudicialEstudo\Util\Cnj;
use RuntimeException;

/** Métodos "Controlado" abrem transação; falha depois de gravar desfaz tudo. */
final class TransacaoTest extends IntegrationTestCase
{
    /** RN que grava processo + evento e DEPOIS falha. */
    private function rnQueFalhaAposGravar(): ProcessoRN
    {
        return new class extends ProcessoRN {
            protected function cadastrarControlado(ProcessoDTO $dto): ProcessoDTO
            {
                parent::cadastrarControlado($dto);
                throw new RuntimeException('falha após gravar');
            }
        };
    }

    #[Test]
    public function excecaoEhEncapsuladaEmNucleoException(): void
    {
        $e = $this->capturarNucleoException(
            fn() => $this->rnQueFalhaAposGravar()->cadastrar($this->novoProcessoDTO(Cnj::montar(2, 2026)))
        );

        $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
    }

    #[Test]
    public function rollbackDesfazProcessoEEventoDeDistribuicao(): void
    {
        $this->capturarNucleoException(
            fn() => $this->rnQueFalhaAposGravar()->cadastrar($this->novoProcessoDTO(Cnj::montar(2, 2026)))
        );

        $this->assertSame(0, new ProcessoRN()->contar(new ProcessoDTO()));
        $this->assertCount(0, new EventoRN()->listar(new EventoDTO()));
    }

    #[Test]
    public function chamarMetodoControladoEmLoopAbreUmaTransacaoPorIteracao(): void
    {
        $id = $this->cadastrarProcesso();
        $banco = Banco::getInstance();
        $antes = $banco->totalTransacoes;

        for ($i = 1; $i <= 5; $i++) {
            new ParteRN()->cadastrar($this->novaParte($id, "Réu $i"));
        }

        $this->assertSame(5, $banco->totalTransacoes - $antes);
    }

    #[Test]
    public function transacaoEnvolventeAgrupaAsChamadas(): void
    {
        $id = $this->cadastrarProcesso();
        $banco = Banco::getInstance();
        $antes = $banco->totalTransacoes;

        $banco->abrirTransacao();
        for ($i = 1; $i <= 5; $i++) {
            new ParteRN()->cadastrar($this->novaParte($id, "Réu $i"));
        }
        $banco->confirmarTransacao();

        $this->assertSame(1, $banco->totalTransacoes - $antes);
    }

    private function novaParte(int $idProcesso, string $nome): ParteDTO
    {
        $parte = new ParteDTO();
        $parte->setNumIdProcesso($idProcesso)->setStrNome($nome)->setStrPolo('P')->setStrTipo('REU');
        return $parte;
    }
}
