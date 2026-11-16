<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

/** Cache que não guarda nada: a aplicação funciona normalmente, só sem o ganho de desempenho. */
final class SemCache implements Cache
{
    public function buscar(string $chave): ?array
    {
        return null;
    }

    public function guardar(string $chave, array $valor, int $segundos): void {}

    public function remover(string $chave): void {}
}
