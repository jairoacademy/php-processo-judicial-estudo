<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Util;

/**
 * Numeração única CNJ (Res. 65/2008): NNNNNNN-DD.AAAA.J.TR.OOOO
 * J = ramo da Justiça (8 = Estadual), TR = código do tribunal (ex.: 19 = RJ), OOOO = unidade de origem.
 * DV = 98 - ((NNNNNNN AAAA J TR OOOO 00) mod 97).
 */
final class Cnj
{
    private const REGEX = '/^(\d{7})-(\d{2})\.(\d{4})\.(\d)\.(\d{2})\.(\d{4})$/';

    public static function calcularDv(string $seq, string $ano, string $j, string $tr, string $orgao): string
    {
        $resto = 0;
        foreach (str_split($seq . $ano . $j . $tr . $orgao . '00') as $d) {
            $resto = ($resto * 10 + (int) $d) % 97; // mod 97 em partes: o número de 20 dígitos estoura int
        }
        return str_pad((string) (98 - $resto), 2, '0', STR_PAD_LEFT);
    }

    public static function validar(string $numero): bool
    {
        if (!preg_match(self::REGEX, $numero, $m)) {
            return false;
        }
        return self::calcularDv($m[1], $m[3], $m[4], $m[5], $m[6]) === $m[2];
    }

    public static function montar(int $seq, int $ano, int $j = 8, int $tr = 19, int $orgao = 1): string
    {
        $s = str_pad((string) $seq, 7, '0', STR_PAD_LEFT);
        $a = (string) $ano;
        $jj = (string) $j;
        $t = str_pad((string) $tr, 2, '0', STR_PAD_LEFT);
        $o = str_pad((string) $orgao, 4, '0', STR_PAD_LEFT);
        return sprintf('%s-%s.%s.%s.%s.%s', $s, self::calcularDv($s, $a, $jj, $t, $o), $a, $jj, $t, $o);
    }
}
