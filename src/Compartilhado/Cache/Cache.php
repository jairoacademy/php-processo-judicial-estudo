<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Compartilhado\Cache;

/**
 * Cache de chave e valor. Os valores são arrays (os dados de um DTO, por exemplo),
 * guardados com prazo de validade. Implementações: RedisCache (produção),
 * MemoriaCache (testes) e SemCache (quando não há Redis configurado).
 */
interface Cache
{
    /** @return array<string, mixed>|null null quando a chave não existe ou expirou */
    public function buscar(string $chave): ?array;

    /** @param array<string, mixed> $valor */
    public function guardar(string $chave, array $valor, int $segundos): void;

    public function remover(string $chave): void;
}
