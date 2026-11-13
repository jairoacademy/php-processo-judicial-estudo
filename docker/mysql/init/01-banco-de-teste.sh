#!/bin/sh
# Roda só na primeira inicialização do volume do MySQL.
# Cria o banco usado pelos testes de integração, separado do banco de desenvolvimento.
set -e

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
SQL
