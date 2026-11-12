<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Processo;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

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
