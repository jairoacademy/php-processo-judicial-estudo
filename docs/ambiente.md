# Ambiente de desenvolvimento

[Voltar ao README](../README.md)

## Containers
O `docker compose` sobe três serviços:

| Serviço | Imagem | Papel |
|---|---|---|
| `php` | PHP-FPM 8.4 (Alpine), com Composer e Xdebug | Executa a aplicação, os testes e as ferramentas |
| `nginx` | nginx 1.30 | Recebe as requisições na porta 8090 e repassa os `.php` ao php-fpm por FastCGI (porta 9000) |
| `mysql` | MySQL 8.4 | Banco da aplicação (`processo`) e dos testes (`processo_test`) |

| Arquivo | Papel |
|---|---|
| `compose.yaml` | Os três serviços, a rede entre eles e o volume do MySQL |
| `docker/php/Dockerfile` | Imagem multi-stage: `base` (pdo_mysql, intl, opcache) e `dev` (Xdebug, Composer, mesmo UID do usuário do host) |
| `docker/php/conf.d/` | `php.ini` comum e de desenvolvimento |
| `docker/nginx/default.conf` | Configuração do nginx; só a pasta `public/` fica exposta |
| `docker/mysql/init/` | Script da primeira inicialização do MySQL: cria o banco de testes |
| `.env.example` | Variáveis do ambiente; copie para `.env`, que fica fora do git |

## Variáveis de ambiente (`.env`)
| Variável | Uso |
|---|---|
| `APP_PORT` | Porta do nginx no host (padrão 8090) |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Conexão da aplicação e das migrations |
| `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `MYSQL_PORT` | Configuração do container do MySQL |

## Comandos do dia a dia
Todos rodam dentro do container `php`:

| Comando | O que faz |
|---|---|
| `docker compose exec php composer check` | Estilo, análise estática e testes (o mesmo que o CI roda) |
| `docker compose exec php composer test` | Todos os testes |
| `docker compose exec php composer test:unit` | Só os testes unitários (sem banco) |
| `docker compose exec php vendor/bin/phpunit --filter CnjTest` | Um teste específico |
| `docker compose exec php composer stan` | PHPStan |
| `docker compose exec php composer cs:fix` | Corrige o estilo de código |
| `docker compose exec php composer db:status` | Migrations aplicadas e pendentes |
| `docker compose exec php php demo.php` | Demonstração: 50 transações num loop x 1 transação envolvente |

Para parar: `docker compose down`. Os dados do MySQL ficam no volume `mysql-data` e continuam lá na próxima subida (`docker compose down -v` apaga o volume).

## Rodar sem Docker
Requisitos: PHP 8.4+ (extensões `pdo_mysql` e `intl`), Composer e um MySQL 8.

1. Crie os bancos `processo` e `processo_test` (charset `utf8mb4`).
2. Defina as variáveis `DB_*` apontando para o seu MySQL.
3. Rode:
```
composer install
composer db:migrate
composer test
php -S localhost:8090 -t public    # servidor embutido, só para desenvolvimento
```
