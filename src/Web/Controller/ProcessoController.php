<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web\Controller;

use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\NucleoDTO;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Web\NaoEncontradoException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Telas do processo. Controller fino: lê a requisição, chama a RN e escolhe a resposta.
 * Regra de negócio e SQL não entram aqui.
 */
final class ProcessoController extends Controller
{
    private const POR_PAGINA = 10;

    /** ?acao=processo_listar (também a tela inicial) */
    public function listar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $numero = $this->texto($requisicao, 'q');
        $situacao = $this->texto($requisicao, 'sit');
        $pagina = max(0, $this->inteiro($requisicao, 'p'));

        $filtro = new ProcessoDTO();
        if ($numero !== '') {
            $filtro->setStrNumero($numero, NucleoDTO::OPER_LIKE);
        }
        if (isset(ProcessoRN::SITUACOES[$situacao])) {
            $filtro->setStrSituacao($situacao);
        }
        $rn = new ProcessoRN();
        $total = $rn->contar($filtro);
        $filtro->setOrdNumIdProcesso('DESC')->setNumMaxRegistrosRetorno(self::POR_PAGINA)->setNumPaginaAtual($pagina);

        return $this->pagina('processo/lista', 'Processos', [
            'processos' => $rn->listar($filtro),
            'total' => $total,
            'numero' => $numero,
            'situacao' => $situacao,
            'pagina' => $pagina,
            'temProxima' => ($pagina + 1) * self::POR_PAGINA < $total,
        ]);
    }

    /** GET ?acao=processo_cadastrar */
    public function formulario(ServerRequestInterface $requisicao): ResponseInterface
    {
        return $this->pagina('processo/cadastro', 'Novo processo');
    }

    /** POST ?acao=processo_cadastrar */
    public function cadastrar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $processo = new ProcessoDTO();
        $processo->setStrNumero($this->texto($requisicao, 'numero'))
            ->setStrClasse($this->texto($requisicao, 'classe'))
            ->setStrAssunto($this->texto($requisicao, 'assunto'))
            ->setStrOrgaoJulgador($this->texto($requisicao, 'orgao'))
            ->setStrSinSigilo($this->marcado($requisicao, 'sigilo') ? 'S' : 'N');
        $processo = new ProcessoRN()->cadastrar($processo);

        return $this->redirecionar('?acao=processo_consultar&id=' . $processo->getNumIdProcesso(), 'Processo distribuído.');
    }

    /** ?acao=processo_consultar&id=N */
    public function consultar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $id = $this->inteiro($requisicao, 'id');
        $filtro = new ProcessoDTO();
        $filtro->setNumIdProcesso($id);
        $processo = new ProcessoRN()->consultar($filtro) ?? throw new NaoEncontradoException('Processo inexistente.');

        $filtroPartes = new ParteDTO();
        $filtroPartes->setNumIdProcesso($id)->setOrdStrPolo();
        $filtroEventos = new EventoDTO();
        $filtroEventos->setNumIdProcesso($id)->setOrdNumIdEvento('DESC');

        return $this->pagina('processo/consulta', 'Processo ' . $processo->getStrNumero(), [
            'processo' => $processo,
            'partes' => new ParteRN()->listar($filtroPartes),
            'eventos' => new EventoRN()->listar($filtroEventos),
        ]);
    }
}
