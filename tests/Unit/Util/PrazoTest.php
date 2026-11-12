<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Tests\Unit\Util;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ProcessoJudicialEstudo\Util\Prazo;

final class PrazoTest extends TestCase
{
    private const SEXTA = '2026-11-06';

    #[Test]
    public function prazoEmDiasUteisPulaFimDeSemana(): void
    {
        $fim = Prazo::somarDiasUteis(new DateTimeImmutable(self::SEXTA), 5);

        $this->assertSame('2026-11-13', $fim->format('Y-m-d'));
    }

    #[Test]
    public function prazoPulaFeriadoInformado(): void
    {
        $fim = Prazo::somarDiasUteis(new DateTimeImmutable(self::SEXTA), 1, ['2026-11-09']);

        $this->assertSame('2026-11-10', $fim->format('Y-m-d'));
    }
}
