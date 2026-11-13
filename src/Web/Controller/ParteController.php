<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web\Controller;

use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ParteController extends Controller
{
    /** POST ?acao=parte_cadastrar&id=N (formulário da tela de consulta) */
    public function cadastrar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $idProcesso = $this->inteiro($requisicao, 'id');
        $parte = new ParteDTO();
        $parte->setNumIdProcesso($idProcesso)
            ->setStrNome($this->texto($requisicao, 'nome'))
            ->setStrDocumento($this->texto($requisicao, 'doc'))
            ->setStrPolo($this->texto($requisicao, 'polo'))
            ->setStrTipo($this->texto($requisicao, 'tipo'));
        new ParteRN()->cadastrar($parte);

        return $this->redirecionar('?acao=processo_consultar&id=' . $idProcesso, 'Parte adicionada.');
    }
}
