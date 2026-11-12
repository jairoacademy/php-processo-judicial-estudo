<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Parte;

use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;

final class ParteRNTest extends IntegrationTestCase
{
    #[Test]
    public function cadastraParteValida(): void
    {
        $id = $this->cadastrarProcesso();

        $parte = (new ParteRN())->cadastrar($this->novaParte($id, '529.982.247-25'));

        $this->assertSame(1, $parte->getNumIdParte());
    }

    #[Test]
    public function rejeitaCpfInvalido(): void
    {
        $id = $this->cadastrarProcesso();

        $e = $this->capturarNucleoException(fn() => (new ParteRN())->cadastrar($this->novaParte($id, '123')));

        $this->assertTrue($e->contemValidacoes());
    }

    #[Test]
    public function rejeitaParteEmProcessoInexistente(): void
    {
        $this->expectException(NucleoException::class);

        (new ParteRN())->cadastrar($this->novaParte(9999, '529.982.247-25'));
    }

    private function novaParte(int $idProcesso, string $documento): ParteDTO
    {
        $parte = new ParteDTO();
        $parte->setNumIdProcesso($idProcesso)
            ->setStrNome('Maria da Silva')
            ->setStrDocumento($documento)
            ->setStrPolo('A')
            ->setStrTipo('AUTOR');
        return $parte;
    }
}
