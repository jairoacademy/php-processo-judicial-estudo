<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Evento;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

/**
 * Métodos mágicos gerados pelo NucleoDTO::__call, declarados para o PHPStan e a IDE.
 *
 * @method $this setNumIdEvento(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumIdEvento()
 * @method $this retNumIdEvento()
 * @method $this setOrdNumIdEvento(string $direcao = 'ASC')
 * @method bool isSetNumIdEvento()
 * @method $this unSetNumIdEvento()
 * @method $this setNumIdProcesso(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumIdProcesso()
 * @method $this retNumIdProcesso()
 * @method $this setOrdNumIdProcesso(string $direcao = 'ASC')
 * @method bool isSetNumIdProcesso()
 * @method $this unSetNumIdProcesso()
 * @method $this setStrTipo(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrTipo()
 * @method $this retStrTipo()
 * @method $this setOrdStrTipo(string $direcao = 'ASC')
 * @method bool isSetStrTipo()
 * @method $this unSetStrTipo()
 * @method $this setStrDescricao(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrDescricao()
 * @method $this retStrDescricao()
 * @method $this setOrdStrDescricao(string $direcao = 'ASC')
 * @method bool isSetStrDescricao()
 * @method $this unSetStrDescricao()
 * @method $this setDthEvento(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getDthEvento()
 * @method $this retDthEvento()
 * @method $this setOrdDthEvento(string $direcao = 'ASC')
 * @method bool isSetDthEvento()
 * @method $this unSetDthEvento()
 * @method $this setStrUsuario(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrUsuario()
 * @method $this retStrUsuario()
 * @method $this setOrdStrUsuario(string $direcao = 'ASC')
 * @method bool isSetStrUsuario()
 * @method $this unSetStrUsuario()
 * @method $this setNumDiasPrazo(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumDiasPrazo()
 * @method $this retNumDiasPrazo()
 * @method $this setOrdNumDiasPrazo(string $direcao = 'ASC')
 * @method bool isSetNumDiasPrazo()
 * @method $this unSetNumDiasPrazo()
 * @method $this setDtaPrazoFinal(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getDtaPrazoFinal()
 * @method $this retDtaPrazoFinal()
 * @method $this setOrdDtaPrazoFinal(string $direcao = 'ASC')
 * @method bool isSetDtaPrazoFinal()
 * @method $this unSetDtaPrazoFinal()
 */
class EventoDTO extends NucleoDTO
{
    public function getStrNomeTabela(): string
    {
        return 'evento';
    }

    public function pk(): string
    {
        return 'NumIdEvento';
    }

    public function montar(): array
    {
        return [
            'NumIdEvento' => 'id_evento',
            'NumIdProcesso' => 'id_processo',
            'StrTipo' => 'sta_tipo',
            'StrDescricao' => 'descricao',
            'DthEvento' => 'dth_evento',
            'StrUsuario' => 'usuario',
            'NumDiasPrazo' => 'num_dias_prazo',
            'DtaPrazoFinal' => 'dta_prazo_final',
        ];
    }
}
