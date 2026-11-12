<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

/**
 * DTO como "construtor de consultas" (padrão inspirado na documentação pública do SEI).
 *   set<Attr>($v, $oper)   -> valor de gravação OU filtro (WHERE) numa consulta
 *   ret<Attr>()            -> quais colunas a consulta retorna
 *   setOrd<Attr>('ASC')    -> ordenação (a sequência das chamadas define a precedência)
 *   get/isSet/unSet<Attr>
 * As subclasses declaram tabela, PK e o mapa atributo => coluna.
 */
abstract class NucleoDTO
{
    public const OPER_IGUAL = '=';
    public const OPER_DIFERENTE = '<>';
    public const OPER_MAIOR = '>';
    public const OPER_MENOR = '<';
    public const OPER_MAIOR_IGUAL = '>=';
    public const OPER_MENOR_IGUAL = '<=';
    public const OPER_LIKE = 'LIKE';
    public const OPER_IN = 'IN';
    public const OPER_NOT_IN = 'NOT IN';

    public const OPERADORES = [
        self::OPER_IGUAL, self::OPER_DIFERENTE, self::OPER_MAIOR, self::OPER_MENOR,
        self::OPER_MAIOR_IGUAL, self::OPER_MENOR_IGUAL, self::OPER_LIKE, self::OPER_IN, self::OPER_NOT_IN,
    ];

    /** @var array<string, mixed> */
    private array $valores = [];
    /** @var array<string, string> */
    private array $operadores = [];
    /** @var array<string, bool> */
    private array $retorno = [];
    /** @var array<string, string> */
    private array $ordem = [];
    private ?int $maxRegistros = null;
    private int $paginaAtual = 0;

    abstract public function getStrNomeTabela(): string;

    abstract public function pk(): string;

    /** @return array<string, string> atributo => coluna */
    abstract public function montar(): array;

    public function __call(string $nome, array $args): mixed
    {
        if (!preg_match('/^(setOrd|set|get|ret|isSet|unSet)([A-Z]\w*)$/', $nome, $m)) {
            throw new \BadMethodCallException("Método inexistente: $nome");
        }
        [, $acao, $attr] = $m;
        if (!isset($this->montar()[$attr])) {
            throw new \BadMethodCallException("Atributo inexistente em " . static::class . ": $attr");
        }
        switch ($acao) {
            case 'set':
                $this->valores[$attr] = $args[0] ?? null;
                if (isset($args[1])) {
                    if (!in_array($args[1], self::OPERADORES, true)) {
                        throw new \InvalidArgumentException('Operador inválido.');
                    }
                    $this->operadores[$attr] = $args[1];
                }
                return $this;
            case 'get':
                return $this->valores[$attr] ?? null;
            case 'isSet':
                return array_key_exists($attr, $this->valores);
            case 'unSet':
                unset($this->valores[$attr], $this->operadores[$attr]);
                return $this;
            case 'ret':
                $this->retorno[$attr] = true;
                return $this;
            case 'setOrd':
                $dir = strtoupper((string) ($args[0] ?? 'ASC'));
                $this->ordem[$attr] = $dir === 'DESC' ? 'DESC' : 'ASC';
                return $this;
        }
        return null;
    }

    public function setNumMaxRegistrosRetorno(?int $max): static
    {
        $this->maxRegistros = $max;
        return $this;
    }

    public function getNumMaxRegistrosRetorno(): ?int
    {
        return $this->maxRegistros;
    }

    /** Página base 0. */
    public function setNumPaginaAtual(int $pagina): static
    {
        $this->paginaAtual = max(0, $pagina);
        return $this;
    }

    public function getNumPaginaAtual(): int
    {
        return $this->paginaAtual;
    }

    /** @return array<string, mixed> */
    public function valores(): array
    {
        return $this->valores;
    }

    /** @return array<string, string> */
    public function operadores(): array
    {
        return $this->operadores;
    }

    /** @return array<string, bool> */
    public function retorno(): array
    {
        return $this->retorno;
    }

    /** @return array<string, string> */
    public function ordem(): array
    {
        return $this->ordem;
    }
}
