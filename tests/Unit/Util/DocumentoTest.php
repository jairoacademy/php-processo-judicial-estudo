<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProcessoJudicialEstudo\Util\Documento;

final class DocumentoTest extends TestCase
{
    #[Test]
    #[DataProvider('cpfsValidos')]
    public function aceitaCpfValido(string $cpf): void
    {
        $this->assertTrue(Documento::validarCpf($cpf));
    }

    /** @return array<string, array{string}> */
    public static function cpfsValidos(): array
    {
        return [
            'com máscara' => ['529.982.247-25'],
            'só dígitos' => ['52998224725'],
        ];
    }

    #[Test]
    #[DataProvider('cpfsInvalidos')]
    public function rejeitaCpfInvalido(string $cpf): void
    {
        $this->assertFalse(Documento::validarCpf($cpf));
    }

    /** @return array<string, array{string}> */
    public static function cpfsInvalidos(): array
    {
        return [
            'dígitos repetidos' => ['111.111.111-11'],
            'dígito verificador errado' => ['529.982.247-24'],
            'curto demais' => ['123'],
        ];
    }

    #[Test]
    public function aceitaCnpjValido(): void
    {
        $this->assertTrue(Documento::validarCnpj('11.222.333/0001-81'));
    }

    #[Test]
    public function rejeitaCnpjComDigitoErrado(): void
    {
        $this->assertFalse(Documento::validarCnpj('11.222.333/0001-80'));
    }
}
