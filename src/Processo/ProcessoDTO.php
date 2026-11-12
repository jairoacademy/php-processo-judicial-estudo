<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Processo;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

/**
 * Métodos mágicos gerados pelo NucleoDTO::__call, declarados para o PHPStan e a IDE.
 *
 * @method $this setNumIdProcesso(int|list<int>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method int|null getNumIdProcesso()
 * @method $this retNumIdProcesso()
 * @method $this setOrdNumIdProcesso(string $direcao = 'ASC')
 * @method bool isSetNumIdProcesso()
 * @method $this unSetNumIdProcesso()
 * @method $this setStrNumero(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrNumero()
 * @method $this retStrNumero()
 * @method $this setOrdStrNumero(string $direcao = 'ASC')
 * @method bool isSetStrNumero()
 * @method $this unSetStrNumero()
 * @method $this setStrClasse(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrClasse()
 * @method $this retStrClasse()
 * @method $this setOrdStrClasse(string $direcao = 'ASC')
 * @method bool isSetStrClasse()
 * @method $this unSetStrClasse()
 * @method $this setStrAssunto(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrAssunto()
 * @method $this retStrAssunto()
 * @method $this setOrdStrAssunto(string $direcao = 'ASC')
 * @method bool isSetStrAssunto()
 * @method $this unSetStrAssunto()
 * @method $this setStrOrgaoJulgador(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrOrgaoJulgador()
 * @method $this retStrOrgaoJulgador()
 * @method $this setOrdStrOrgaoJulgador(string $direcao = 'ASC')
 * @method bool isSetStrOrgaoJulgador()
 * @method $this unSetStrOrgaoJulgador()
 * @method $this setStrSituacao(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrSituacao()
 * @method $this retStrSituacao()
 * @method $this setOrdStrSituacao(string $direcao = 'ASC')
 * @method bool isSetStrSituacao()
 * @method $this unSetStrSituacao()
 * @method $this setStrSinSigilo(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getStrSinSigilo()
 * @method $this retStrSinSigilo()
 * @method $this setOrdStrSinSigilo(string $direcao = 'ASC')
 * @method bool isSetStrSinSigilo()
 * @method $this unSetStrSinSigilo()
 * @method $this setDtaDistribuicao(string|list<string>|null $valor, string $operador = NucleoDTO::OPER_IGUAL)
 * @method string|null getDtaDistribuicao()
 * @method $this retDtaDistribuicao()
 * @method $this setOrdDtaDistribuicao(string $direcao = 'ASC')
 * @method bool isSetDtaDistribuicao()
 * @method $this unSetDtaDistribuicao()
 */
class ProcessoDTO extends NucleoDTO
{
    public function getStrNomeTabela(): string
    {
        return 'processo';
    }

    public function pk(): string
    {
        return 'NumIdProcesso';
    }

    public function montar(): array
    {
        return [
            'NumIdProcesso' => 'id_processo',
            'StrNumero' => 'numero',
            'StrClasse' => 'classe',
            'StrAssunto' => 'assunto',
            'StrOrgaoJulgador' => 'orgao_julgador',
            'StrSituacao' => 'sta_situacao',
            'StrSinSigilo' => 'sin_sigilo',
            'DtaDistribuicao' => 'dta_distribuicao',
        ];
    }
}
