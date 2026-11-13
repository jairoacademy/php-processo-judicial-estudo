<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web\Controller;

use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EventoController extends Controller
{
    /** POST ?acao=evento_cadastrar&id=N (formulário da tela de consulta) */
    public function cadastrar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $idProcesso = $this->inteiro($requisicao, 'id');
        $evento = new EventoDTO();
        $evento->setNumIdProcesso($idProcesso)
            ->setStrTipo($this->texto($requisicao, 'tipo'))
            ->setStrDescricao($this->texto($requisicao, 'descricao'));
        $dias = $this->inteiro($requisicao, 'dias');
        if ($dias > 0) {
            $evento->setNumDiasPrazo($dias);
        }
        new EventoRN()->cadastrar($evento);

        return $this->redirecionar('?acao=processo_consultar&id=' . $idProcesso, 'Evento registrado.');
    }
}
