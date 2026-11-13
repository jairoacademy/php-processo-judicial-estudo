<?php

declare(strict_types=1);

// Front controller: toda requisição do navegador entra por aqui.
require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use ProcessoJudicialEstudo\Web\Aplicacao;
use ProcessoJudicialEstudo\Web\Sessao;

// 1. Sessão do usuário (token CSRF e mensagens flash)
session_start();

// 2. Requisição: $_GET, $_POST e $_SERVER viram um objeto PSR-7
$fabrica = new Psr17Factory();
$requisicao = new ServerRequestCreator($fabrica, $fabrica, $fabrica, $fabrica)->fromGlobals();

// 3. A aplicação decide a tela e devolve a resposta (status, cabeçalhos e HTML)
$resposta = new Aplicacao(new Sessao($_SESSION))->atender($requisicao);

// 4. Envia a resposta ao navegador
http_response_code($resposta->getStatusCode());
foreach ($resposta->getHeaders() as $nome => $valores) {
    header($nome . ': ' . implode(', ', $valores));
}
echo $resposta->getBody();
