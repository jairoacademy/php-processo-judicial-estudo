<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

/** Cache num array, só durante a execução. Usado nos testes, para não depender de um Redis rodando. */
final class MemoriaCache implements Cache
{
    /** @var array<string, array<string, mixed>> */
    private array $itens = [];

    public function buscar(string $chave): ?array
    {
        return $this->itens[$chave] ?? null;
    }

    public function guardar(string $chave, array $valor, int $segundos): void
    {
        $this->itens[$chave] = $valor;
    }

    public function remover(string $chave): void
    {
        unset($this->itens[$chave]);
    }
}
