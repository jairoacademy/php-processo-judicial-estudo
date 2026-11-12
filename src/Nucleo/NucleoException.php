<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

/** Análogo ao NucleoException: encapsula a causa e acumula validações de negócio. */
class NucleoException extends \RuntimeException
{
    /** @var list<string> */
    private array $validacoes = [];

    public function adicionarValidacao(string $mensagem): void
    {
        $this->validacoes[] = $mensagem;
    }

    public function contemValidacoes(): bool
    {
        return $this->validacoes !== [];
    }

    /** @return list<string> */
    public function getValidacoes(): array
    {
        return $this->validacoes;
    }

    /** Lança a si mesma se houver validações acumuladas. */
    public function lancarValidacoes(): void
    {
        if ($this->contemValidacoes()) {
            throw $this;
        }
    }
}
