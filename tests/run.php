<?php
declare(strict_types=1);

// Testes sem PHPUnit: php tests/run.php  (banco SQLite em memória)
require __DIR__ . '/../src/bootstrap.php';

use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Nucleo\NucleoDTO;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Util\Cnj;
use ProcessoJudicialEstudo\Util\Documento;
use ProcessoJudicialEstudo\Util\Prazo;

Banco::configurar('sqlite::memory:');

$falhas = 0;
$total = 0;
function verifica(string $nome, bool $ok): void
{
    global $falhas, $total;
    $total++;
    if (!$ok) {
        $falhas++;
    }
    echo ($ok ? '  ok   ' : '  FALHA ') . $nome . PHP_EOL;
}
function lanca(callable $f): ?NucleoException
{
    try {
        $f();
    } catch (NucleoException $e) {
        return $e;
    }
    return null;
}
function novoProcesso(string $numero, string $orgao = '1ª Vara Cível'): ProcessoDTO
{
    $p = new ProcessoDTO();
    $p->setStrNumero($numero)->setStrClasse('Procedimento Comum Cível')->setStrAssunto('Indenização por Dano Moral')->setStrOrgaoJulgador($orgao);
    return $p;
}

echo "Util\n";
verifica('CNJ gerado é válido', Cnj::validar(Cnj::montar(1234, 2026)));
verifica('CNJ com DV errado é rejeitado', !Cnj::validar('0001234-00.2026.8.19.0001'));
verifica('CPF válido', Documento::validarCpf('529.982.247-25'));
verifica('CPF inválido', !Documento::validarCpf('111.111.111-11'));
verifica('CNPJ válido', Documento::validarCnpj('11.222.333/0001-81'));
$sexta = new DateTimeImmutable('2026-11-06'); // sexta
verifica('prazo de 5 dias úteis pula fim de semana', Prazo::somarDiasUteis($sexta, 5)->format('Y-m-d') === '2026-11-13');
verifica('prazo respeita feriado informado', Prazo::somarDiasUteis($sexta, 1, ['2026-11-09'])->format('Y-m-d') === '2026-11-10');

echo "Processo\n";
$rn = new ProcessoRN();
$n1 = Cnj::montar(1, 2026);
$p = $rn->cadastrar(novoProcesso($n1));
verifica('cadastrar devolve PK', $p->getNumIdProcesso() === 1);
verifica('número inválido gera validação', lanca(fn() => $rn->cadastrar(novoProcesso('123')))?->contemValidacoes() === true);
verifica('número duplicado é rejeitado', lanca(fn() => $rn->cadastrar(novoProcesso($n1))) !== null);

$f = new EventoDTO();
$f->setNumIdProcesso(1);
verifica('distribuição gerou evento', count((new EventoRN())->listar($f)) === 1);

echo "Transação (rollback)\n";
$antes = $rn->contar(new ProcessoDTO());
$eventosAntes = count((new EventoRN())->listar(new EventoDTO()));
// RN que grava processo + evento e DEPOIS falha: tudo deve ser desfeito (atomicidade)
$rnQueFalha = new class extends ProcessoRN {
    protected function cadastrarControlado(ProcessoDTO $dto): ProcessoDTO
    {
        parent::cadastrarControlado($dto);
        throw new \RuntimeException('falha após gravar');
    }
};
$e = lanca(fn() => $rnQueFalha->cadastrar(novoProcesso(Cnj::montar(2, 2026))));
verifica('exceção é encapsulada em NucleoException', $e !== null && $e->getPrevious() instanceof \RuntimeException);
verifica('rollback desfez o processo', $rn->contar(new ProcessoDTO()) === $antes);
verifica('rollback desfez o evento de distribuição', count((new EventoRN())->listar(new EventoDTO())) === $eventosAntes);
$parteRuim = new ParteDTO();
$parteRuim->setNumIdProcesso(9999)->setStrNome('Fulano')->setStrPolo('A')->setStrTipo('AUTOR');
verifica('parte em processo inexistente é rejeitada', lanca(fn() => (new ParteRN())->cadastrar($parteRuim)) !== null);

echo "Partes e eventos\n";
$parte = new ParteDTO();
$parte->setNumIdProcesso(1)->setStrNome('Maria da Silva')->setStrDocumento('529.982.247-25')->setStrPolo('A')->setStrTipo('AUTOR');
verifica('parte válida cadastrada', (new ParteRN())->cadastrar($parte)->getNumIdParte() === 1);
$parte2 = new ParteDTO();
$parte2->setNumIdProcesso(1)->setStrNome('João')->setStrDocumento('123')->setStrPolo('P')->setStrTipo('REU');
verifica('CPF inválido é rejeitado', lanca(fn() => (new ParteRN())->cadastrar($parte2))?->contemValidacoes() === true);

$int = new EventoDTO();
$int->setNumIdProcesso(1)->setStrTipo('INTIMACAO')->setStrDescricao('Intimação para réplica')->setNumDiasPrazo(15);
$int = (new EventoRN())->cadastrar($int);
verifica('intimação calcula prazo final', $int->getDtaPrazoFinal() !== null);
$semPrazo = new EventoDTO();
$semPrazo->setNumIdProcesso(1)->setStrTipo('CITACAO')->setStrDescricao('Citação');
verifica('citação sem prazo é rejeitada', lanca(fn() => (new EventoRN())->cadastrar($semPrazo)) !== null);

echo "Filtros do DTO\n";
$rn->cadastrar(novoProcesso(Cnj::montar(3, 2026), '2ª Vara Cível'));
$rn->cadastrar(novoProcesso(Cnj::montar(4, 2026), '3ª Vara Cível'));
$f = new ProcessoDTO();
$f->setStrOrgaoJulgador(['2ª Vara Cível', '3ª Vara Cível'], NucleoDTO::OPER_IN)->setOrdStrNumero('DESC');
$lista = $rn->listar($f);
verifica('IN + ORDER BY DESC', count($lista) === 2 && $lista[0]->getStrNumero() === Cnj::montar(4, 2026));
$f = new ProcessoDTO();
$f->setStrOrgaoJulgador('Vara', NucleoDTO::OPER_LIKE)->setNumMaxRegistrosRetorno(2)->setNumPaginaAtual(1)->setOrdNumIdProcesso();
verifica('LIKE + paginação (página 1 de 2 itens)', count($rn->listar($f)) === 1);
$f = new ProcessoDTO();
$f->setStrNumero($n1)->retStrNumero()->retStrClasse();
$r = $rn->consultar($f);
verifica('ret limita colunas', $r->getStrNumero() === $n1 && $r->getStrAssunto() === null);

echo "Baixa\n";
$alt = new ProcessoDTO();
$alt->setNumIdProcesso(1)->setStrSituacao('B');
$rn->alterarSituacao($alt);
$ev = new EventoDTO();
$ev->setNumIdProcesso(1)->setStrTipo('PETICAO')->setStrDescricao('Petição após baixa');
verifica('processo baixado não admite eventos', lanca(fn() => (new EventoRN())->cadastrar($ev)) !== null);

echo "Transações reais abertas até aqui: " . Banco::getInstance()->totalTransacoes . PHP_EOL;
echo "\n$total verificações, $falhas falha(s)\n";
exit($falhas === 0 ? 0 : 1);
