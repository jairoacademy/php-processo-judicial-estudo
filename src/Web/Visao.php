<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Monta as páginas: roda o template da página (o "miolo") e o encaixa no layout (a "moldura").
 * Os templates ficam em templates/ e recebem as variáveis de $dados.
 */
final class Visao
{
    public function __construct(
        private readonly string $pastaTemplates,
        private readonly Sessao $sessao,
    ) {}

    /**
     * @param array<string, mixed> $dados
     * @param list<string> $erros
     */
    public function pagina(string $template, string $titulo, array $dados = [], int $status = 200, array $erros = []): ResponseInterface
    {
        $miolo = $this->renderizar($template, $dados + ['csrf' => $this->sessao->tokenCsrf()]);
        $html = $this->renderizar('layout', [
            'titulo' => $titulo,
            'conteudo' => $miolo,
            'mensagens' => $this->sessao->consumirFlash(),
            'erros' => $erros,
        ]);
        return new Response($status, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * Executa templates/<nome>.php e devolve, como texto, o HTML que ele imprimiu.
     * extract() transforma cada chave de $dados numa variável do template;
     * ob_start() desvia a impressão para a memória em vez de mandá-la ao navegador.
     *
     * @param array<string, mixed> $dados
     */
    private function renderizar(string $nome, array $dados): string
    {
        $arquivo = $this->pastaTemplates . '/' . $nome . '.php';
        $executar = static function (string $__arquivo, array $__dados): void {
            extract($__dados, EXTR_SKIP);
            require $__arquivo;
        };

        ob_start();
        try {
            $executar($arquivo, $dados);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
