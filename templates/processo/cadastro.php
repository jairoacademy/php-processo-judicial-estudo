<?php
/**
 * @var string $csrf
 */
?>
<form method="post" action="?acao=processo_cadastrar">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <label>Número CNJ <input name="numero" size="28" placeholder="0000001-00.2026.8.19.0001"></label>
  <label>Classe <input name="classe"></label>
  <label>Assunto <input name="assunto"></label>
  <label>Órgão julgador <input name="orgao"></label>
  <label><input type="checkbox" name="sigilo"> Segredo de justiça</label>
  <button>Distribuir</button>
</form>
