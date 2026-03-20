<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute un index sur la colonne titre de la table demande pour optimiser la recherche.
 */
final class Version20260320000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l\'index idx_demande_titre sur demande.titre pour optimiser les recherches LIKE';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_demande_titre ON demande (titre)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_demande_titre ON demande');
    }
}