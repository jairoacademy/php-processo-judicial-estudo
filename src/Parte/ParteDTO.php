<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Parte;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

/**
 * Métodos mágicos gerados pelo NucleoDTO::__call, declarados para o PHPStan e a IDE.
 *
 * @method $this setNumIdParte(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumIdParte()
 * @method $this retNumIdParte()
 * @method $this setOrdNumIdParte(string $direcao = 'ASC')
 * @method bool isSetNumIdParte()
 * @method $this unSetNumIdParte()
 * @method $this setNumIdProcesso(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumIdProcesso()
 * @method $this retNumIdProcesso()
 * @method $this setOrdNumIdProcesso(string $direcao = 'ASC')
 * @method bool isSetNumIdProcesso()
 * @method $this unSetNumIdProcesso()
 * @method $this setStrNome(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrNome()
 * @method $this retStrNome()
 * @method $this setOrdStrNome(string $direcao = 'ASC')
 * @method bool isSetStrNome()
 * @method $this unSetStrNome()
 * @method $this setStrDocumento(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrDocumento()
 * @method $this retStrDocumento()
 * @method $this setOrdStrDocumento(string $direcao = 'ASC')
 * @method bool isSetStrDocumento()
 * @method $this unSetStrDocumento()
 * @method $this setStrPolo(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrPolo()
 * @method $this retStrPolo()
 * @method $this setOrdStrPolo(string $direcao = 'ASC')
 * @method bool isSetStrPolo()
 * @method $this unSetStrPolo()
 * @method $this setStrTipo(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrTipo()
 * @method $this retStrTipo()
 * @method $this setOrdStrTipo(string $direcao = 'ASC')
 * @method bool isSetStrTipo()
 * @method $this unSetStrTipo()
 */
class ParteDTO extends NucleoDTO
{
    public function getStrNomeTabela(): string
    {
        return 'parte';
    }

    public function pk(): string
    {
        return 'NumIdParte';
    }

    public function montar(): array
    {
        return [
            'NumIdParte' => 'id_parte',
            'NumIdProcesso' => 'id_processo',
            'StrNome' => 'nome',
            'StrDocumento' => 'documento',
            'StrPolo' => 'sta_polo',
            'StrTipo' => 'sta_tipo',
        ];
    }
}
