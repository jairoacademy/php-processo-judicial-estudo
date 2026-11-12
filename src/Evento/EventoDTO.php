<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Evento;

use ProcessoJudicialEstudo\Nucleo\NucleoDTO;

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
