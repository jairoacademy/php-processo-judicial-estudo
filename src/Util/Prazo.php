<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Util;

use DateTimeImmutable;

/**
 * Contagem de prazo em dias úteis (CPC art. 219 e 224): exclui o dia do começo e
 * conta apenas dias úteis. Simplificação: só pula sábado/domingo e feriados informados
 * (os feriados e suspensões reais variam por tribunal e seriam uma tabela própria).
 */
final class Prazo
{
    /** @param string[] $feriados datas Y-m-d */
    public static function somarDiasUteis(DateTimeImmutable $inicio, int $dias, array $feriados = []): DateTimeImmutable
    {
        $data = $inicio;
        while ($dias > 0) {
            $data = $data->modify('+1 day');
            $ehFimDeSemana = (int) $data->format('N') >= 6;
            if (!$ehFimDeSemana && !in_array($data->format('Y-m-d'), $feriados, true)) {
                $dias--;
            }
        }
        return $data;
    }
}
