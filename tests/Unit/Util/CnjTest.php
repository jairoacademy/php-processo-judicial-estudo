<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProcessoJudicialEstudo\Util\Cnj;

final class CnjTest extends TestCase
{
    #[Test]
    public function montaNumeroComDigitoVerificadorCorreto(): void
    {
        $this->assertSame('0000001-69.2026.8.19.0001', Cnj::montar(1, 2026));
    }

    #[Test]
    public function numeroMontadoEhValido(): void
    {
        $this->assertTrue(Cnj::validar(Cnj::montar(1234, 2026)));
    }

    #[Test]
    #[DataProvider('numerosInvalidos')]
    public function rejeitaNumeroInvalido(string $numero): void
    {
        $this->assertFalse(Cnj::validar($numero));
    }

    /** @return array<string, array{string}> */
    public static function numerosInvalidos(): array
    {
        return [
            'dígito verificador errado' => ['0001234-00.2026.8.19.0001'],
            'curto demais' => ['123'],
            'vazio' => [''],
        ];
    }
}
