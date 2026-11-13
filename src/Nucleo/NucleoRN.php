<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

/**
 * Façade de regra de negócio. Métodos protegidos terminados em "Conectado" ou
 * "Controlado" são chamados de fora SEM o sufixo (ex.: $rn->cadastrar($dto)); o __call
 * abre a conexão (Conectado) ou conexão + transação (Controlado), faz commit ao fim
 * e rollback em exceção. Convenção inspirada na documentação pública do SEI;
 * o despacho implementado aqui é uma interpretação própria, não código de terceiros.
 */
abstract class NucleoRN
{
    abstract protected function inicializarObjBanco(): Banco;

    protected function getObjBanco(): Banco
    {
        return $this->inicializarObjBanco();
    }

    /** @param array<int, mixed> $args */
    public function __call(string $nome, array $args): mixed
    {
        foreach (['Controlado', 'Conectado'] as $sufixo) {
            $real = $nome . $sufixo;
            if (!method_exists($this, $real)) {
                continue;
            }
            if (count($args) !== 1) {
                throw new NucleoException("$real exige exatamente um parâmetro (DTO ou array).");
            }
            $banco = $this->getObjBanco();
            $banco->abrirConexao();
            if ($sufixo === 'Conectado') {
                return $this->$real($args[0]);
            }
            $banco->abrirTransacao();
            try {
                $resultado = $this->$real($args[0]);
                $banco->confirmarTransacao();
                return $resultado;
            } catch (NucleoException $e) {
                $banco->cancelarTransacao();
                throw $e;
            } catch (\Throwable $e) {
                $banco->cancelarTransacao();
                throw new NucleoException("Erro em $nome.", 0, $e);
            }
        }
        throw new \BadMethodCallException('Método inexistente: ' . static::class . "::$nome");
    }
}
