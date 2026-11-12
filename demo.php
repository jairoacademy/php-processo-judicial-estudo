<?php

declare(strict_types=1);

// php demo.php  -> mostra a armadilha de chamar método Controlado dentro de loop
require __DIR__ . '/vendor/autoload.php';

use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Util\Cnj;

Banco::configurar('sqlite::memory:');
$banco = Banco::getInstance();

$p = new ProcessoDTO();
$p->setStrNumero(Cnj::montar(10, 2026))->setStrClasse('Execução Fiscal')->setStrAssunto('IPTU')->setStrOrgaoJulgador('Vara de Fazenda Pública');
$p = new ProcessoRN()->cadastrar($p);

echo "Transações após cadastrar processo (+ evento): {$banco->totalTransacoes}\n";

// RUIM: 50 chamadas externas = 50 transações (e 50 commits; se a 30ª falhar, 29 já foram gravadas)
$antes = $banco->totalTransacoes;
$rn = new ParteRN();
for ($i = 1; $i <= 50; $i++) {
    $parte = new ParteDTO();
    $parte->setNumIdProcesso($p->getNumIdProcesso())->setStrNome("Réu $i")->setStrPolo('P')->setStrTipo('REU');
    $rn->cadastrar($parte);
}
echo 'Loop com chamada externa: ' . ($banco->totalTransacoes - $antes) . " transações\n";

// MELHOR: abrir UMA transação em volta e chamar a RN dentro (entra na transação aberta)
$antes = $banco->totalTransacoes;
$banco->abrirTransacao();
try {
    for ($i = 51; $i <= 100; $i++) {
        $parte = new ParteDTO();
        $parte->setNumIdProcesso($p->getNumIdProcesso())->setStrNome("Réu $i")->setStrPolo('P')->setStrTipo('REU');
        $rn->cadastrar($parte);
    }
    $banco->confirmarTransacao();
} catch (Throwable $e) {
    $banco->cancelarTransacao();
    throw $e;
}
echo 'Loop dentro de uma transação: ' . ($banco->totalTransacoes - $antes) . " transação\n";
