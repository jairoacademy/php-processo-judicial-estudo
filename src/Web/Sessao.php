<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web;

/**
 * Acesso à sessão do usuário: token CSRF e mensagens flash.
 * Recebe o array por referência ($_SESSION na aplicação, um array comum nos testes),
 * então o código não depende da superglobal e pode ser testado sem servidor.
 */
final class Sessao
{
    /** @var array<mixed> */
    private array $dados;

    /** @param array<mixed> $dados */
    public function __construct(array &$dados)
    {
        $this->dados = &$dados;
    }

    public function tokenCsrf(): string
    {
        $token = $this->dados['csrf'] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            $this->dados['csrf'] = $token;
        }
        return $token;
    }

    /** Mensagem exibida uma única vez, na próxima página (padrão PRG). */
    public function adicionarFlash(string $mensagem): void
    {
        $this->dados['flash'] = [...$this->flashes(), $mensagem];
    }

    /** @return list<string> as mensagens pendentes; depois de lidas, são apagadas */
    public function consumirFlash(): array
    {
        $mensagens = $this->flashes();
        unset($this->dados['flash']);
        return $mensagens;
    }

    /** @return list<string> */
    private function flashes(): array
    {
        $flash = $this->dados['flash'] ?? [];
        return is_array($flash) ? array_values(array_filter($flash, is_string(...))) : [];
    }
}
