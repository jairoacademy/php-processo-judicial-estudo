<?php
declare(strict_types=1);

namespace ProcessoJudicialEstudo\Nucleo;

/**
 * Acesso a dados genérico guiado pelo DTO. Um BD por tabela (subclasses vazias).
 * Nomes de coluna vêm do DTO (nunca da entrada do usuário); valores sempre via bind.
 */
class NucleoBD
{
    public function __construct(protected Banco $banco)
    {
    }

    /** @return NucleoDTO[] */
    public function listar(NucleoDTO $dto): array
    {
        $cols = $dto->montar();
        $sel = $dto->retorno() !== [] ? array_intersect_key($cols, $dto->retorno()) : $cols;
        $params = [];
        $sql = 'SELECT ' . implode(', ', array_map(
            static fn(string $attr, string $col) => "$col AS $attr",
            array_keys($sel),
            $sel
        )) . ' FROM ' . $dto->getStrNomeTabela() . $this->where($dto, $params);

        if ($dto->ordem() !== []) {
            $ord = [];
            foreach ($dto->ordem() as $attr => $dir) {
                $ord[] = $cols[$attr] . ' ' . $dir; // $dir já validado no DTO
            }
            $sql .= ' ORDER BY ' . implode(', ', $ord);
        }
        if ($dto->getNumMaxRegistrosRetorno() !== null) {
            $max = $dto->getNumMaxRegistrosRetorno();
            $sql .= ' LIMIT ' . (int) $max . ' OFFSET ' . (int) ($dto->getNumPaginaAtual() * $max);
        }

        $resultado = [];
        foreach ($this->banco->consultarSql($sql, $params) as $linha) {
            $obj = new ($dto::class)();
            foreach ($linha as $attr => $valor) {
                $obj->{'set' . $attr}($valor);
            }
            $resultado[] = $obj;
        }
        return $resultado;
    }

    public function consultar(NucleoDTO $dto): ?NucleoDTO
    {
        $dto->setNumMaxRegistrosRetorno(1);
        return $this->listar($dto)[0] ?? null;
    }

    public function contar(NucleoDTO $dto): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) AS n FROM ' . $dto->getStrNomeTabela() . $this->where($dto, $params);
        return (int) $this->banco->consultarSql($sql, $params)[0]['n'];
    }

    public function cadastrar(NucleoDTO $dto): NucleoDTO
    {
        $cols = $dto->montar();
        $pk = $dto->pk();
        $campos = $marcas = $params = [];
        foreach ($dto->valores() as $attr => $valor) {
            if ($attr === $pk && $valor === null) {
                continue;
            }
            $campos[] = $cols[$attr];
            $marcas[] = ':' . $cols[$attr];
            $params[':' . $cols[$attr]] = $valor;
        }
        $this->banco->executarSql(
            'INSERT INTO ' . $dto->getStrNomeTabela() . ' (' . implode(', ', $campos) . ') VALUES (' . implode(', ', $marcas) . ')',
            $params
        );
        $dto->{'set' . $pk}($this->banco->ultimoId());
        return $dto;
    }

    public function alterar(NucleoDTO $dto): int
    {
        $cols = $dto->montar();
        $pk = $dto->pk();
        if (!$dto->{'isSet' . $pk}()) {
            throw new NucleoException('Alteração exige a chave primária.');
        }
        $sets = $params = [];
        foreach ($dto->valores() as $attr => $valor) {
            if ($attr === $pk) {
                continue;
            }
            $sets[] = $cols[$attr] . ' = :' . $cols[$attr];
            $params[':' . $cols[$attr]] = $valor;
        }
        if ($sets === []) {
            return 0;
        }
        $params[':pk'] = $dto->{'get' . $pk}();
        return $this->banco->executarSql(
            'UPDATE ' . $dto->getStrNomeTabela() . ' SET ' . implode(', ', $sets) . ' WHERE ' . $cols[$pk] . ' = :pk',
            $params
        );
    }

    public function excluir(NucleoDTO $dto): int
    {
        $params = [];
        $where = $this->where($dto, $params);
        if ($where === '') {
            throw new NucleoException('Exclusão sem filtro não é permitida.');
        }
        return $this->banco->executarSql('DELETE FROM ' . $dto->getStrNomeTabela() . $where, $params);
    }

    private function where(NucleoDTO $dto, array &$params): string
    {
        $cols = $dto->montar();
        $partes = [];
        $i = 0;
        foreach ($dto->valores() as $attr => $valor) {
            $col = $cols[$attr];
            $oper = $dto->operadores()[$attr] ?? NucleoDTO::OPER_IGUAL;
            if ($oper === NucleoDTO::OPER_IN || $oper === NucleoDTO::OPER_NOT_IN) {
                $marcas = [];
                foreach ((array) $valor as $v) {
                    $k = ':w' . $i++;
                    $params[$k] = $v;
                    $marcas[] = $k;
                }
                $partes[] = $marcas === [] ? ($oper === NucleoDTO::OPER_IN ? '1 = 0' : '1 = 1')
                    : "$col $oper (" . implode(', ', $marcas) . ')';
                continue;
            }
            $k = ':w' . $i++;
            $params[$k] = $oper === NucleoDTO::OPER_LIKE ? '%' . $valor . '%' : $valor;
            $partes[] = "$col $oper $k";
        }
        return $partes === [] ? '' : ' WHERE ' . implode(' AND ', $partes);
    }
}
