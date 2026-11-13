<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Util\Cnj;

/**
 * Base dos testes de integração: cada teste recebe um banco SQLite em memória novo,
 * então nenhum teste depende de dados gravados por outro.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        Banco::configurar('sqlite::memory:');
    }

    protected function novoProcessoDTO(string $numero, string $orgao = '1ª Vara Cível'): ProcessoDTO
    {
        $p = new ProcessoDTO();
        $p->setStrNumero($numero)
            ->setStrClasse('Procedimento Comum Cível')
            ->setStrAssunto('Indenização por Dano Moral')
            ->setStrOrgaoJulgador($orgao);
        return $p;
    }

    /** Cadastra um processo válido e devolve o id gerado. */
    protected function cadastrarProcesso(int $sequencial = 1, string $orgao = '1ª Vara Cível'): int
    {
        $id = new ProcessoRN()->cadastrar($this->novoProcessoDTO(Cnj::montar($sequencial, 2026), $orgao))->getNumIdProcesso();
        $this->assertNotNull($id);
        return $id;
    }

    /** Executa $acao e devolve a NucleoException lançada; falha o teste se nada for lançado. */
    protected function capturarNucleoException(callable $acao): NucleoException
    {
        try {
            $acao();
        } catch (NucleoException $e) {
            return $e;
        }
        $this->fail('Era esperada uma NucleoException.');
    }
}
