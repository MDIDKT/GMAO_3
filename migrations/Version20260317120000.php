<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renommer TypePhoto COMPLEMENT → CONSTAT dans la table photo.
 */
final class Version20260317120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme le type de photo COMPLEMENT en CONSTAT';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE photo SET type_photo = 'CONSTAT' WHERE type_photo = 'COMPLEMENT'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE photo SET type_photo = 'COMPLEMENT' WHERE type_photo = 'CONSTAT'");
    }
}
