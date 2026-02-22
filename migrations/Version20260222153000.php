<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on demande.numero';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande ADD UNIQUE INDEX UNIQ_DEMANDE_NUMERO (numero)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande DROP INDEX UNIQ_DEMANDE_NUMERO');
    }
}
