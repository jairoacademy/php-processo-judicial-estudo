<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Integration\Web;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use ProcessoJudicialEstudo\Tests\Integration\IntegrationTestCase;
use ProcessoJudicialEstudo\Util\Cnj;
use ProcessoJudicialEstudo\Web\Aplicacao;
use ProcessoJudicialEstudo\Web\Sessao;
use Psr\Http\Message\ResponseInterface;

/**
 * Testes das telas sem servidor HTTP: monta a requisição PSR-7 à mão, passa pela aplicação
 * inteira (middlewares, roteador, controller, RN e MySQL) e confere a resposta.
 * Em Java: o MockMvc do Spring.
 */
final class TelasTest extends IntegrationTestCase
{
    /** @var array<mixed> faz o papel do $_SESSION */
    private array $dadosSessao = [];
    private Sessao $sessao;
    private Aplicacao $aplicacao;
    private Psr17Factory $fabrica;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dadosSessao = [];
        $this->sessao = new Sessao($this->dadosSessao);
        $this->aplicacao = new Aplicacao($this->sessao);
        $this->fabrica = new Psr17Factory();
    }

    #[Test]
    public function listaVaziaAbreComStatus200(): void
    {
        $resposta = $this->get('');

        $this->assertSame(200, $resposta->getStatusCode());
        $this->assertStringContainsString('0 resultado(s)', (string) $resposta->getBody());
    }

    #[Test]
    public function formularioDeCadastroTrazOTokenCsrf(): void
    {
        $resposta = $this->get('acao=processo_cadastrar');

        $this->assertStringContainsString('value="' . $this->sessao->tokenCsrf() . '"', (string) $resposta->getBody());
    }

    #[Test]
    public function postSemTokenCsrfEhRecusado(): void
    {
        $resposta = $this->post('acao=processo_cadastrar', $this->dadosProcesso(), comToken: false);

        $this->assertSame(400, $resposta->getStatusCode());
        $this->assertSame(0, $this->contarProcessos());
    }

    #[Test]
    public function cadastroValidoRedirecionaParaAConsultaComMensagem(): void
    {
        $resposta = $this->post('acao=processo_cadastrar', $this->dadosProcesso());

        $this->assertSame(303, $resposta->getStatusCode());
        $this->assertMatchesRegularExpression('/^\?acao=processo_consultar&id=\d+$/', $resposta->getHeaderLine('Location'));

        $consulta = $this->get(ltrim($resposta->getHeaderLine('Location'), '?'));
        $this->assertSame(200, $consulta->getStatusCode());
        $this->assertStringContainsString('Processo distribuído.', (string) $consulta->getBody());
        $this->assertStringContainsString('DISTRIBUICAO', (string) $consulta->getBody());

        $deNovo = $this->get(ltrim($resposta->getHeaderLine('Location'), '?'));
        $this->assertStringNotContainsString('Processo distribuído.', (string) $deNovo->getBody(), 'A mensagem flash aparece uma vez só');
    }

    #[Test]
    public function cadastroInvalidoMostraAsValidacoesCom422(): void
    {
        $resposta = $this->post('acao=processo_cadastrar', ['numero' => '123', 'classe' => '', 'assunto' => 'x', 'orgao' => 'x']);

        $this->assertSame(422, $resposta->getStatusCode());
        $this->assertStringContainsString('Número inválido', (string) $resposta->getBody());
        $this->assertStringContainsString('Classe é obrigatório', (string) $resposta->getBody());
    }

    #[Test]
    public function nomeComHtmlEhEscapadoNaTela(): void
    {
        $id = $this->cadastrarProcesso();
        $this->post("acao=parte_cadastrar&id=$id", ['nome' => '<script>alert(1)</script>', 'doc' => '', 'polo' => 'A', 'tipo' => 'AUTOR']);

        $corpo = (string) $this->get("acao=processo_consultar&id=$id")->getBody();

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $corpo);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $corpo);
    }

    #[Test]
    public function eventoComPrazoApareceNaConsulta(): void
    {
        $id = $this->cadastrarProcesso();

        $resposta = $this->post("acao=evento_cadastrar&id=$id", ['tipo' => 'INTIMACAO', 'descricao' => 'Intimação para réplica', 'dias' => '15']);

        $this->assertSame(303, $resposta->getStatusCode());
        $corpo = (string) $this->get("acao=processo_consultar&id=$id")->getBody();
        $this->assertStringContainsString('Evento registrado.', $corpo);
        $this->assertStringContainsString('Intimação para réplica', $corpo);
    }

    #[Test]
    public function acaoDesconhecidaDevolve404(): void
    {
        $this->assertSame(404, $this->get('acao=nao_existe')->getStatusCode());
    }

    #[Test]
    public function processoInexistenteDevolve404(): void
    {
        $this->assertSame(404, $this->get('acao=processo_consultar&id=999999')->getStatusCode());
    }

    #[Test]
    public function acaoSoDePostRecusaGetCom405(): void
    {
        $resposta = $this->get('acao=parte_cadastrar&id=1');

        $this->assertSame(405, $resposta->getStatusCode());
        $this->assertSame('POST', $resposta->getHeaderLine('Allow'));
    }

    private function get(string $query): ResponseInterface
    {
        parse_str($query, $parametros);
        $requisicao = $this->fabrica->createServerRequest('GET', '/?' . $query)->withQueryParams($parametros);
        return $this->aplicacao->atender($requisicao);
    }

    /** @param array<string, string> $corpo */
    private function post(string $query, array $corpo, bool $comToken = true): ResponseInterface
    {
        parse_str($query, $parametros);
        if ($comToken) {
            $corpo['csrf'] = $this->sessao->tokenCsrf();
        }
        $requisicao = $this->fabrica->createServerRequest('POST', '/?' . $query)
            ->withQueryParams($parametros)
            ->withParsedBody($corpo);
        return $this->aplicacao->atender($requisicao);
    }

    /** @return array<string, string> */
    private function dadosProcesso(): array
    {
        return [
            'numero' => Cnj::montar(1, 2026),
            'classe' => 'Procedimento Comum Cível',
            'assunto' => 'Indenização por Dano Moral',
            'orgao' => '1ª Vara Cível',
        ];
    }

    private function contarProcessos(): int
    {
        return new \ProcessoJudicialEstudo\Processo\ProcessoRN()->contar(new \ProcessoJudicialEstudo\Processo\ProcessoDTO());
    }
}
