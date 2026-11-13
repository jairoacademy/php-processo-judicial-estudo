<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Índice para a consulta por órgão julgador. Sem ele, o EXPLAIN de
 * "WHERE orgao_julgador IN (...)" mostrava type=ALL (varredura da tabela inteira).
 * A situação (sta_situacao) ficou sem índice de propósito: só tem 3 valores, e um índice
 * de baixa cardinalidade raramente é escolhido pelo otimizador.
 */
final class Version20261113000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria o índice i02_processo_orgao_julgador';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX i02_processo_orgao_julgador ON processo (orgao_julgador)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX i02_processo_orgao_julgador ON processo');
    }
}
