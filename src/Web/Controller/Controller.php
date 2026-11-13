<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web\Controller;

use Nyholm\Psr7\Response;
use ProcessoJudicialEstudo\Web\Sessao;
use ProcessoJudicialEstudo\Web\Visao;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base dos controllers (a mesma ideia do AbstractController do Symfony): ler a entrada
 * da requisição já tipada, mostrar uma página e redirecionar.
 */
abstract class Controller
{
    public function __construct(
        protected readonly Visao $visao,
        protected readonly Sessao $sessao,
    ) {}

    /** Texto da query string (GET) ou do formulário (POST); ausente ou de tipo inesperado vira $padrao. */
    protected function texto(ServerRequestInterface $requisicao, string $nome, string $padrao = ''): string
    {
        $valor = $this->entrada($requisicao)[$nome] ?? $padrao;
        return is_string($valor) ? trim($valor) : $padrao;
    }

    /** Inteiro da query string ou do formulário; valor não numérico vira 0. */
    protected function inteiro(ServerRequestInterface $requisicao, string $nome): int
    {
        $valor = $this->entrada($requisicao)[$nome] ?? 0;
        return is_numeric($valor) ? (int) $valor : 0;
    }

    /** Checkbox marcado no formulário. */
    protected function marcado(ServerRequestInterface $requisicao, string $nome): bool
    {
        return isset($this->entrada($requisicao)[$nome]);
    }

    /** @param array<string, mixed> $dados */
    protected function pagina(string $template, string $titulo, array $dados = []): ResponseInterface
    {
        return $this->visao->pagina($template, $titulo, $dados);
    }

    /** Depois de gravar: mensagem para a próxima página e 303 See Other (padrão PRG). */
    protected function redirecionar(string $destino, string $mensagem): ResponseInterface
    {
        $this->sessao->adicionarFlash($mensagem);
        return new Response(303, ['Location' => $destino]);
    }

    /** @return array<mixed> query string e, num POST, os campos do formulário */
    private function entrada(ServerRequestInterface $requisicao): array
    {
        $corpo = $requisicao->getParsedBody();
        return (is_array($corpo) ? $corpo : []) + $requisicao->getQueryParams();
    }
}
