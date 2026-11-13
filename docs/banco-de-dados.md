# Banco de dados e migrations

[Voltar ao README](../README.md)

## MySQL
MySQL 8.4 com InnoDB e `utf8mb4_0900_ai_ci`. A conexão vem de `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` (`src/Nucleo/ConfiguracaoBanco.php`), lidas tanto pela aplicação (PDO, com prepared statements reais) quanto pelas migrations (Doctrine DBAL).

Os testes de integração usam um banco separado, `processo_test`, definido no `phpunit.xml`; eles nunca tocam o banco de desenvolvimento.

## Migrations
O esquema é versionado com **Doctrine Migrations**, em SQL puro (`migrations/`). A tabela `doctrine_migration_versions` guarda o que já foi aplicado. Uma migration aplicada nunca é alterada: mudanças entram numa migration nova.

```
docker compose exec php composer db:status     # aplicadas e pendentes
docker compose exec php composer db:migrate    # aplica as pendentes
docker compose exec php composer db:rollback   # desfaz a última
```

| Arquivo | Papel |
|---|---|
| `migrations.php` | Configuração. Sem transação em volta das migrations, porque o MySQL faz commit implícito a cada DDL; por isso cada migration é pequena e tem `down()` |
| `migrations-db.php` | Conexão DBAL a partir das mesmas variáveis de ambiente |
| `migrations/Version20261113000001.php` | Esquema inicial: `processo`, `parte` e `evento` |
| `migrations/Version20261113000002.php` | Índice `i02_processo_orgao_julgador` |

O CI prova que as migrations são reversíveis: depois dos testes, desfaz todas e aplica de novo.

## Esquema
```
processo 1 ──< parte
         1 ──< evento
```

| Tabela | Conteúdo |
|---|---|
| `processo` | Número CNJ (único), classe, assunto, órgão julgador, situação (A/S/B), sigilo (S/N), data de distribuição |
| `parte` | Nome, CPF/CNPJ, polo (A/P/T) e tipo (autor, réu, advogado...) |
| `evento` | Tipo, descrição, data e hora, usuário, prazo em dias úteis e data final do prazo |

## Convenções de nome
| Prefixo | Significado | Exemplo |
|---|---|---|
| `pk_` | Chave primária | `pk_processo` |
| `ak_` | Chave alternativa (única) | `ak_processo_numero` |
| `fk_` | Chave estrangeira | `fk_parte_processo` |
| `ck_` | Restrição `CHECK` | `ck_processo_situacao` |
| `i01_`, `i02_`... | Índice | `i01_evento_processo` |
| `sta_` | Status (domínio fechado de valores) | `sta_situacao` |
| `sin_` | Sim/Não (S/N) | `sin_sigilo` |
| `dta_` / `dth_` | Data / data e hora | `dta_distribuicao`, `dth_evento` |
| `num_` | Número | `num_dias_prazo` |

## Índices e `EXPLAIN`
Com 20 mil processos e 100 mil eventos de teste:
- O filtro por órgão julgador (`orgao_julgador IN (...)`) passou de varredura da tabela inteira (`type=ALL`, cerca de 20 mil linhas) para `range` (cerca de 1 mil linhas) com o índice `i02_processo_orgao_julgador`.
- A busca do número por prefixo (`LIKE '0000123%'`) usa o índice único `ak_processo_numero`; a busca por trecho (`LIKE '%0000123%'`), usada hoje na tela, varre a tabela.
- A situação (`sta_situacao`) ficou sem índice de propósito: com só 3 valores, o otimizador prefere ler a tabela.
