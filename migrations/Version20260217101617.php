<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217101617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batiment (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, etage VARCHAR(255) DEFAULT NULL, actif TINYINT NOT NULL, site_id INT DEFAULT NULL, INDEX IDX_F5FAB00CF6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse LONGTEXT DEFAULT NULL, code_postal VARCHAR(255) NOT NULL, ville VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, actif TINYINT NOT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_694309E49E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE batiment ADD CONSTRAINT FK_F5FAB00CF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E49E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batiment DROP FOREIGN KEY FK_F5FAB00CF6BD1646');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E49E6B1585');
        $this->addSql('DROP TABLE batiment');
        $this->addSql('DROP TABLE site');
    }
}
