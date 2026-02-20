<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220142237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'demande'"
        );

        $hasPriorite = in_array('priorite', $columns, true);
        $legacyPrioriteColumn = null;

        foreach ($columns as $column) {
            if (!is_string($column)) {
                continue;
            }

            if (preg_match('/^priorit/i', $column) === 1 && $column !== 'priorite') {
                $legacyPrioriteColumn = $column;
                break;
            }
        }

        if (!$hasPriorite && $legacyPrioriteColumn !== null) {
            $safeLegacyColumn = str_replace('`', '``', $legacyPrioriteColumn);
            $this->addSql(sprintf(
                'ALTER TABLE demande CHANGE `%s` priorite VARCHAR(255) NOT NULL',
                $safeLegacyColumn
            ));
        }

        $this->abortIf(
            !$hasPriorite && $legacyPrioriteColumn === null,
            'No priorite column found in demande table.'
        );

        $this->addSql(
            'ALTER TABLE demande CHANGE site_id site_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE organisation_id organisation_id INT NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE demande CHANGE site_id site_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE organisation_id organisation_id INT DEFAULT NULL'
        );
    }
}
