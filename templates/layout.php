<?php
/**
 * Layout comum a todas as páginas.
 *
 * @var string $titulo
 * @var string $conteudo HTML já renderizado do template da página
 * @var list<string> $mensagens mensagens flash (sucesso)
 * @var list<string> $erros mensagens de validação
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Processo Judicial</title>
    <style>
        body {
            font: 15px system-ui;
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem
        }

        table {
            border-collapse: collapse;
            width: 100%
        }

        td, th {
            border: 1px solid #ccc;
            padding: .35rem .5rem;
            text-align: left
        }

        .erro {
            background: #fee;
            border: 1px solid #c66;
            padding: .5rem 1.5rem
        }

        .ok {
            background: #efe;
            border: 1px solid #6a6;
            padding: .5rem 1rem
        }

        label {
            display: block;
            margin: .4rem 0
        }

        input, select {
            padding: .3rem
        }
    </style>
</head>

<body>
<h1><a href="?acao=processo_listar" style="text-decoration:none">Processo Judicial</a> – <?= e($titulo) ?></h1>
<?php foreach ($mensagens as $mensagem): ?>
    <p class="ok"><?= e($mensagem) ?></p>
<?php endforeach ?>

<?php if ($erros !== []): ?>
    <ul class="erro">
        <?php foreach ($erros as $erro): ?>
            <li><?= e($erro) ?></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<?= $conteudo /* já escapado pelo template da página */ ?>
</body>
</html>
