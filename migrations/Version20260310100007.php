<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310100007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batiment (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, etage VARCHAR(255) DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, site_id INT DEFAULT NULL, INDEX IDX_F5FAB00CF6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie_equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_267D0C5F9E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priorite VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, motif_rejet LONGTEXT DEFAULT NULL, numero VARCHAR(255) NOT NULL, site_id INT NOT NULL, batiment_id INT DEFAULT NULL, equipement_id INT DEFAULT NULL, user_id INT NOT NULL, organisation_id INT NOT NULL, INDEX IDX_2694D7A5F6BD1646 (site_id), INDEX IDX_2694D7A5D6F6891B (batiment_id), INDEX IDX_2694D7A5806F0F5C (equipement_id), INDEX IDX_2694D7A5A76ED395 (user_id), INDEX IDX_2694D7A59E6B1585 (organisation_id), UNIQUE INDEX UNIQ_DEMANDE_NUMERO (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, marque VARCHAR(255) DEFAULT NULL, modele VARCHAR(255) DEFAULT NULL, numero_de_serie VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, site_id INT DEFAULT NULL, batiment_id INT DEFAULT NULL, categorie_id INT DEFAULT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_B8B4C6F3F6BD1646 (site_id), INDEX IDX_B8B4C6F3D6F6891B (batiment_id), INDEX IDX_B8B4C6F3BCF5E72D (categorie_id), INDEX IDX_B8B4C6F39E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(255) NOT NULL, date_planifiee DATETIME DEFAULT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, compte_rendu LONGTEXT DEFAULT NULL, duree_minutes INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, demande_id INT NOT NULL, technicien_id INT DEFAULT NULL, planificateur_id INT DEFAULT NULL, organisation_id INT NOT NULL, INDEX IDX_D11814AB80E95E18 (demande_id), INDEX IDX_D11814AB13457256 (technicien_id), INDEX IDX_D11814ABA10CE5BD (planificateur_id), INDEX IDX_D11814AB9E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organisation (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_ORGANISATION_NOM (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, taille INT NOT NULL, legende VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, type_photo VARCHAR(255) NOT NULL, demande_id INT DEFAULT NULL, upload_par_id INT NOT NULL, intervention_id INT DEFAULT NULL, INDEX IDX_14B7841880E95E18 (demande_id), INDEX IDX_14B78418CBA5D612 (upload_par_id), INDEX IDX_14B784188EAE3863 (intervention_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse LONGTEXT DEFAULT NULL, code_postal VARCHAR(255) NOT NULL, ville VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, organisation_id INT NOT NULL, INDEX IDX_694309E49E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, actif TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, invitation_token VARCHAR(64) DEFAULT NULL, token_expires_at DATETIME DEFAULT NULL, organisation_id INT NOT NULL, INDEX IDX_8D93D6499E6B1585 (organisation_id), INDEX IDX_USER_INVITATION_TOKEN (invitation_token), INDEX IDX_USER_ORG_ACTIF (organisation_id, actif), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE batiment ADD CONSTRAINT FK_F5FAB00CF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE categorie_equipement ADD CONSTRAINT FK_267D0C5F9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5D6F6891B FOREIGN KEY (batiment_id) REFERENCES batiment (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A59E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3D6F6891B FOREIGN KEY (batiment_id) REFERENCES batiment (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_equipement (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F39E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB80E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB13457256 FOREIGN KEY (technicien_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814ABA10CE5BD FOREIGN KEY (planificateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841880E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418CBA5D612 FOREIGN KEY (upload_par_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784188EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E49E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batiment DROP FOREIGN KEY FK_F5FAB00CF6BD1646');
        $this->addSql('ALTER TABLE categorie_equipement DROP FOREIGN KEY FK_267D0C5F9E6B1585');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5F6BD1646');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5D6F6891B');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5806F0F5C');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5A76ED395');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A59E6B1585');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3F6BD1646');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3D6F6891B');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3BCF5E72D');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F39E6B1585');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB80E95E18');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB13457256');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814ABA10CE5BD');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB9E6B1585');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B7841880E95E18');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418CBA5D612');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784188EAE3863');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E49E6B1585');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499E6B1585');
        $this->addSql('DROP TABLE batiment');
        $this->addSql('DROP TABLE categorie_equipement');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE organisation');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
