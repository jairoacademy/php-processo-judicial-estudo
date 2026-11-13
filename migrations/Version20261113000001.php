<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Esquema inicial: processo, parte e evento.
 * Convenções de nome: pk_, ak_ (chave alternativa/única), fk_, i01_ (índice), ck_ (check);
 * prefixos de coluna sta_ (status), sin_ (S/N), dta_ (data), dth_ (data e hora), num_ (número).
 */
final class Version20261113000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria as tabelas processo, parte e evento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE processo (
              id_processo      INT UNSIGNED NOT NULL AUTO_INCREMENT,
              numero           CHAR(25)     NOT NULL COMMENT 'Número único CNJ formatado: NNNNNNN-DD.AAAA.J.TR.OOOO',
              classe           VARCHAR(100) NOT NULL,
              assunto          VARCHAR(200) NOT NULL,
              orgao_julgador   VARCHAR(100) NOT NULL,
              sta_situacao     CHAR(1)      NOT NULL DEFAULT 'A' COMMENT 'A = em andamento, S = suspenso, B = baixado',
              sin_sigilo       CHAR(1)      NOT NULL DEFAULT 'N',
              dta_distribuicao DATE         NOT NULL,
              CONSTRAINT pk_processo PRIMARY KEY (id_processo),
              CONSTRAINT ak_processo_numero UNIQUE (numero),
              CONSTRAINT ck_processo_situacao CHECK (sta_situacao IN ('A', 'S', 'B')),
              CONSTRAINT ck_processo_sigilo CHECK (sin_sigilo IN ('S', 'N'))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE parte (
              id_parte    INT UNSIGNED NOT NULL AUTO_INCREMENT,
              id_processo INT UNSIGNED NOT NULL,
              nome        VARCHAR(200) NOT NULL,
              documento   VARCHAR(14)  NULL COMMENT 'CPF (11) ou CNPJ (14), só dígitos',
              sta_polo    CHAR(1)      NOT NULL COMMENT 'A = ativo, P = passivo, T = terceiro',
              sta_tipo    VARCHAR(20)  NOT NULL,
              CONSTRAINT pk_parte PRIMARY KEY (id_parte),
              CONSTRAINT fk_parte_processo FOREIGN KEY (id_processo) REFERENCES processo (id_processo),
              CONSTRAINT ck_parte_polo CHECK (sta_polo IN ('A', 'P', 'T')),
              INDEX i01_parte_processo (id_processo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE evento (
              id_evento       INT UNSIGNED      NOT NULL AUTO_INCREMENT,
              id_processo     INT UNSIGNED      NOT NULL,
              sta_tipo        VARCHAR(20)       NOT NULL,
              descricao       VARCHAR(500)      NOT NULL,
              dth_evento      DATETIME          NOT NULL,
              usuario         VARCHAR(50)       NOT NULL,
              num_dias_prazo  SMALLINT UNSIGNED NULL,
              dta_prazo_final DATE              NULL,
              CONSTRAINT pk_evento PRIMARY KEY (id_evento),
              CONSTRAINT fk_evento_processo FOREIGN KEY (id_processo) REFERENCES processo (id_processo),
              INDEX i01_evento_processo (id_processo, dth_evento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE evento');
        $this->addSql('DROP TABLE parte');
        $this->addSql('DROP TABLE processo');
    }
}
