<?php

use ProcessoJudicialEstudo\Processo\ProcessoRN;

/**
 * @var list<ProcessoJudicialEstudo\Processo\ProcessoDTO> $processos
 * @var int $total
 * @var string $numero filtro digitado
 * @var string $situacao filtro escolhido
 * @var int $pagina página atual, começando em 0
 * @var bool $temProxima
 */
$filtros = 'acao=processo_listar&q=' . urlencode($numero) . '&sit=' . urlencode($situacao);
?>
<form method="get">
  <input type="hidden" name="acao" value="processo_listar">
  <input name="q" placeholder="número" value="<?= e($numero) ?>">
  <select name="sit">
    <option value="">Todas</option>
<?php foreach (ProcessoRN::SITUACOES as $codigo => $rotulo): ?>
    <option value="<?= e($codigo) ?>"<?= $situacao === $codigo ? ' selected' : '' ?>><?= e($rotulo) ?></option>
<?php endforeach ?>
  </select>
  <button>Filtrar</button>
  <a href="?acao=processo_cadastrar">+ Novo processo</a>
</form>

<p><?= $total ?> resultado(s)</p>

<table>
  <tr><th>Número</th><th>Classe</th><th>Órgão julgador</th><th>Situação</th></tr>
<?php foreach ($processos as $processo): ?>
  <tr>
    <td><a href="?acao=processo_consultar&id=<?= e($processo->getNumIdProcesso()) ?>"><?= e($processo->getStrNumero()) ?></a></td>
    <td><?= e($processo->getStrClasse()) ?></td>
    <td><?= e($processo->getStrOrgaoJulgador()) ?></td>
    <td><?= e(ProcessoRN::SITUACOES[$processo->getStrSituacao() ?? ''] ?? '') ?></td>
  </tr>
<?php endforeach ?>
</table>

<p>
<?php if ($pagina > 0): ?>
  <a href="?<?= e($filtros) ?>&p=<?= $pagina - 1 ?>">« anterior</a>
<?php endif ?>
<?php if ($temProxima): ?>
  <a href="?<?= e($filtros) ?>&p=<?= $pagina + 1 ?>">próxima »</a>
<?php endif ?>
</p>
