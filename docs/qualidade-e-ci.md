# Testes, qualidade de código e CI

[Voltar ao README](../README.md)

Tudo de uma vez, como o CI faz:
```
docker compose exec php composer check    # estilo + análise estática + testes
```

## Testes (PHPUnit 13)
Configurados em `phpunit.xml`: ordem aleatória, e qualquer warning, notice ou deprecation faz o teste falhar.

| Suíte | Pasta | O que cobre |
|---|---|---|
| `unit` | `tests/Unit` | Número CNJ, CPF/CNPJ, prazo em dias úteis; sem banco |
| `integration` | `tests/Integration` | RN + BD + MySQL real (banco `processo_test`, tabelas esvaziadas antes de cada teste): validações, rollback, transação em loop, filtros do DTO; e as telas (`tests/Integration/Web`), com requisições PSR-7 montadas à mão, sem servidor: CSRF, PRG com flash, 404/405/422 e escape contra XSS |

`composer test` aplica as migrations no banco de testes antes de rodar.

```
docker compose exec php composer test:unit
docker compose exec php composer test:integration
docker compose exec php vendor/bin/phpunit --filter CnjTest
```

## Análise estática e estilo
| Ferramenta | Configuração | O que garante |
|---|---|---|
| PHPStan 2, nível `max`, com `phpstan-strict-rules` e `phpstan-phpunit` | `phpstan.neon` | Tipos corretos sem executar o código. Generics por PHPDoc (`NucleoBD<T>`) e métodos mágicos declarados com `@method` |
| PHP-CS-Fixer, `@PER-CS` (sucessor da PSR-12) e migração para PHP 8.4 | `.php-cs-fixer.dist.php` | Estilo de código uniforme |

```
docker compose exec php composer stan
docker compose exec php composer cs       # só verifica
docker compose exec php composer cs:fix   # corrige
```

## Integração contínua (GitHub Actions)
O workflow `.github/workflows/ci.yml` roda a cada push na `main` e em cada pull request:

| Job | O que faz |
|---|---|
| `qualidade` (PHP 8.4 e 8.5, com MySQL 8.4 como service container) | `composer validate`, `composer install` pelo lock, `composer audit`, PHP-CS-Fixer, PHPStan, PHPUnit e migrations reversíveis (desfaz tudo e aplica de novo) |
| `docker` | Constrói as imagens `base` e `dev` do `docker/php/Dockerfile` |

O PHP 8.4 é a versão do projeto; o 8.5 entra na matriz para antecipar incompatibilidades com a próxima versão.

A branch `main` é protegida: só recebe mudanças por pull request, com os três checks verdes.

O Dependabot (`.github/dependabot.yml`) abre pull requests semanais com atualizações do Composer, das actions e da imagem Docker.
