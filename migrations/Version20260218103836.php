<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218103836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_267D0C5F9E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, marque VARCHAR(255) DEFAULT NULL, modele VARCHAR(255) DEFAULT NULL, numero_de_serie VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, actif TINYINT NOT NULL, site_id INT DEFAULT NULL, batiment_id INT DEFAULT NULL, categorie_id INT DEFAULT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_B8B4C6F3F6BD1646 (site_id), INDEX IDX_B8B4C6F3D6F6891B (batiment_id), INDEX IDX_B8B4C6F3BCF5E72D (categorie_id), INDEX IDX_B8B4C6F39E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE categorie_equipement ADD CONSTRAINT FK_267D0C5F9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3D6F6891B FOREIGN KEY (batiment_id) REFERENCES batiment (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_equipement (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F39E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie_equipement DROP FOREIGN KEY FK_267D0C5F9E6B1585');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3F6BD1646');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3D6F6891B');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3BCF5E72D');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F39E6B1585');
        $this->addSql('DROP TABLE categorie_equipement');
        $this->addSql('DROP TABLE equipement');
    }
}
