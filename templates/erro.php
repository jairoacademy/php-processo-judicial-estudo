<?php
/**
 * Página de erro (400, 404, 405, 422, 500).
 *
 * @var string|null $mensagem
 */
?>
<?php if ($mensagem !== null): ?>
<p><?= e($mensagem) ?></p>
<?php endif ?>
<p><a href="javascript:history.back()">Voltar</a> · <a href="?acao=processo_listar">Lista de processos</a></p>
