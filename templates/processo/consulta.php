<?php

use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoRN;

/**
 * @var ProcessoJudicialEstudo\Processo\ProcessoDTO $processo
 * @var list<ProcessoJudicialEstudo\Parte\ParteDTO> $partes
 * @var list<ProcessoJudicialEstudo\Evento\EventoDTO> $eventos
 * @var string $csrf
 */
$id = $processo->getNumIdProcesso();
?>
<p>
  <?= e($processo->getStrClasse()) ?> – <?= e($processo->getStrAssunto()) ?><br>
  <?= e($processo->getStrOrgaoJulgador()) ?> · <?= e(ProcessoRN::SITUACOES[$processo->getStrSituacao() ?? ''] ?? '') ?>
<?php if ($processo->getStrSinSigilo() === 'S'): ?>
  · <b>segredo de justiça</b>
<?php endif ?>
</p>

<h3>Partes</h3>
<table>
  <tr><th>Polo</th><th>Tipo</th><th>Nome</th></tr>
<?php foreach ($partes as $parte): ?>
  <tr>
    <td><?= e(ParteRN::POLOS[$parte->getStrPolo() ?? ''] ?? '') ?></td>
    <td><?= e($parte->getStrTipo()) ?></td>
    <td><?= e($parte->getStrNome()) ?></td>
  </tr>
<?php endforeach ?>
</table>
<form method="post" action="?acao=parte_cadastrar&id=<?= e($id) ?>">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input name="nome" placeholder="nome">
  <input name="doc" placeholder="CPF/CNPJ">
  <select name="polo">
<?php foreach (ParteRN::POLOS as $codigo => $rotulo): ?>
    <option value="<?= e($codigo) ?>"><?= e($rotulo) ?></option>
<?php endforeach ?>
  </select>
  <select name="tipo">
<?php foreach (ParteRN::TIPOS as $tipo): ?>
    <option><?= e($tipo) ?></option>
<?php endforeach ?>
  </select>
  <button>Adicionar parte</button>
</form>

<h3>Eventos</h3>
<table>
  <tr><th>Data/hora</th><th>Tipo</th><th>Descrição</th><th>Prazo final</th></tr>
<?php foreach ($eventos as $evento): ?>
  <tr>
    <td><?= e($evento->getDthEvento()) ?></td>
    <td><?= e($evento->getStrTipo()) ?></td>
    <td><?= e($evento->getStrDescricao()) ?></td>
    <td><?= e($evento->getDtaPrazoFinal()) ?></td>
  </tr>
<?php endforeach ?>
</table>
<form method="post" action="?acao=evento_cadastrar&id=<?= e($id) ?>">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <select name="tipo">
<?php foreach (EventoRN::TIPOS as $tipo): ?>
    <option><?= e($tipo) ?></option>
<?php endforeach ?>
  </select>
  <input name="descricao" placeholder="descrição">
  <input name="dias" type="number" min="0" placeholder="prazo (dias úteis)">
  <button>Registrar evento</button>
</form>
