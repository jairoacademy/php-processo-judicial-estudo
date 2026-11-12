<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Parte;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

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
