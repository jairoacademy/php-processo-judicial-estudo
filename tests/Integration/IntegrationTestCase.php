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
 * Base dos testes de integração: rodam contra o MySQL real (banco processo_test, definido
 * no phpunit.xml). As tabelas são esvaziadas antes de cada teste, então nenhum teste
 * depende de dados gravados por outro.
 *
 * Por que não abrir uma transação no setUp e desfazê-la no tearDown? Porque no Banco a
 * falha numa transação aninhada desfaz também a externa; um teste que provoca erro e
 * continua gravando deixaria dados para trás. Esvaziar as tabelas é simples e seguro.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        $banco = Banco::getInstance();
        $banco->cancelarTransacao(); // garante que nenhum teste anterior deixou transação aberta
        foreach (['evento', 'parte', 'processo'] as $tabela) { // filhas antes da mãe (FK)
            $banco->executarSql("DELETE FROM $tabela");
        }
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
