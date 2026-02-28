<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226072637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(255) NOT NULL, date_planifiee DATETIME DEFAULT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, compte_rendu LONGTEXT DEFAULT NULL, duree_minutes INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, demande_id INT NOT NULL, technicien_id INT DEFAULT NULL, planificateur_id INT DEFAULT NULL, organisation_id INT NOT NULL, INDEX IDX_D11814AB80E95E18 (demande_id), INDEX IDX_D11814AB13457256 (technicien_id), INDEX IDX_D11814ABA10CE5BD (planificateur_id), INDEX IDX_D11814AB9E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB80E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB13457256 FOREIGN KEY (technicien_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814ABA10CE5BD FOREIGN KEY (planificateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE photo ADD intervention_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784188EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('CREATE INDEX IDX_14B784188EAE3863 ON photo (intervention_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB80E95E18');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB13457256');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814ABA10CE5BD');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB9E6B1585');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784188EAE3863');
        $this->addSql('DROP INDEX IDX_14B784188EAE3863 ON photo');
        $this->addSql('ALTER TABLE photo DROP intervention_id');
    }
}
