<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web;

use Closure;
use Nyholm\Psr7\Response;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Web\Controller\EventoController;
use ProcessoJudicialEstudo\Web\Controller\ParteController;
use ProcessoJudicialEstudo\Web\Controller\ProcessoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Coração da camada web: recebe a requisição e devolve a resposta.
 * 1. confere o token CSRF de todo formulário enviado (POST);
 * 2. procura a ação na tabela de rotas (?acao=<entidade>_<acao>) e chama o controller;
 * 3. transforma qualquer erro numa página de erro com o status HTTP certo.
 * Em Java, faz o papel do DispatcherServlet com um @ControllerAdvice.
 */
final class Aplicacao
{
    private const ACAO_PADRAO = 'processo_listar';

    private readonly Visao $visao;

    /** @var array<string, array<string, Closure(ServerRequestInterface): ResponseInterface>> ação => [método HTTP => método do controller] */
    private readonly array $rotas;

    public function __construct(private readonly Sessao $sessao)
    {
        $this->visao = new Visao(dirname(__DIR__, 2) . '/templates', $sessao);
        $processo = new ProcessoController($this->visao, $sessao);
        $parte = new ParteController($this->visao, $sessao);
        $evento = new EventoController($this->visao, $sessao);

        // Tabela de rotas: $objeto->metodo(...) guarda o método para ser chamado depois
        $this->rotas = [
            'processo_listar' => ['GET' => $processo->listar(...)],
            'processo_cadastrar' => ['GET' => $processo->formulario(...), 'POST' => $processo->cadastrar(...)],
            'processo_consultar' => ['GET' => $processo->consultar(...)],
            'parte_cadastrar' => ['POST' => $parte->cadastrar(...)],
            'evento_cadastrar' => ['POST' => $evento->cadastrar(...)],
        ];
    }

    public function atender(ServerRequestInterface $requisicao): ResponseInterface
    {
        if ($requisicao->getMethod() === 'POST' && !$this->tokenCsrfValido($requisicao)) {
            return $this->erro(400, 'Requisição inválida', 'Formulário expirado ou inválido. Recarregue a página e tente de novo.');
        }

        $acao = $requisicao->getQueryParams()['acao'] ?? self::ACAO_PADRAO;
        $rota = is_string($acao) ? ($this->rotas[$acao] ?? null) : null;
        if ($rota === null) {
            return $this->erro(404, 'Não encontrado', 'Página inexistente.');
        }
        $metodoDoController = $rota[$requisicao->getMethod()] ?? null;
        if ($metodoDoController === null) {
            return $this->erro(405, 'Requisição inválida', 'Método HTTP não permitido nesta página.')
                ->withHeader('Allow', implode(', ', array_keys($rota)));
        }

        try {
            return $metodoDoController($requisicao);
        } catch (NucleoException $e) {
            // Validação de negócio: mostra as mensagens. Falha técnica encapsulada: só o log vê o detalhe.
            if ($e->getPrevious() !== null) {
                error_log((string) $e->getPrevious());
            }
            $mensagens = $e->contemValidacoes() ? $e->getValidacoes() : [$e->getMessage()];
            return $this->visao->pagina('erro', 'Não foi possível concluir', ['mensagem' => null], 422, $mensagens);
        } catch (NaoEncontradoException $e) {
            return $this->erro(404, 'Não encontrado', $e->getMessage());
        } catch (Throwable $e) {
            error_log((string) $e);
            return $this->erro(500, 'Erro interno', 'Ocorreu um erro inesperado. Tente novamente mais tarde.');
        }
    }

    private function tokenCsrfValido(ServerRequestInterface $requisicao): bool
    {
        $corpo = $requisicao->getParsedBody();
        $enviado = is_array($corpo) ? ($corpo['csrf'] ?? '') : '';
        return is_string($enviado) && hash_equals($this->sessao->tokenCsrf(), $enviado);
    }

    private function erro(int $status, string $titulo, string $mensagem): ResponseInterface
    {
        try {
            return $this->visao->pagina('erro', $titulo, ['mensagem' => $mensagem], $status);
        } catch (Throwable) {
            return new Response($status, ['Content-Type' => 'text/plain; charset=utf-8'], $mensagem);
        }
    }
}
