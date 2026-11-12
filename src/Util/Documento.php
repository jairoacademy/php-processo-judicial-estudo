<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Util;

/** Validação de CPF e CNPJ (apenas dígitos verificadores). */
final class Documento
{
    public static function somenteDigitos(string $s): string
    {
        return preg_replace('/\D+/', '', $s) ?? '';
    }

    public static function validarCpf(string $cpf): bool
    {
        $c = self::somenteDigitos($cpf);
        if (strlen($c) !== 11 || preg_match('/^(\d)\1{10}$/', $c)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $c[$i] * ($t + 1 - $i);
            }
            $dv = ($soma * 10) % 11 % 10;
            if ((int) $c[$t] !== $dv) {
                return false;
            }
        }
        return true;
    }

    public static function validarCnpj(string $cnpj): bool
    {
        $c = self::somenteDigitos($cnpj);
        if (strlen($c) !== 14 || preg_match('/^(\d)\1{13}$/', $c)) {
            return false;
        }
        foreach ([12, 13] as $t) {
            $soma = 0;
            $peso = $t - 7;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $c[$i] * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }
            $dv = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
            if ((int) $c[$t] !== $dv) {
                return false;
            }
        }
        return true;
    }

    public static function validar(string $doc): bool
    {
        return match (strlen(self::somenteDigitos($doc))) {
            11 => self::validarCpf($doc),
            14 => self::validarCnpj($doc),
            default => false,
        };
    }
}
