<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220141604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande CHANGE site_id site_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE organisation_id organisation_id INT NOT NULL, CHANGE priorite priorite VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande CHANGE site_id site_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE organisation_id organisation_id INT DEFAULT NULL, CHANGE priorite priorit?e VARCHAR(255) NOT NULL');
    }
}
