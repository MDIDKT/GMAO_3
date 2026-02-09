<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209140822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, brand VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, serial_number VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, organisation_id INT DEFAULT NULL, site_id INT DEFAULT NULL, INDEX IDX_B8B4C6F39E6B1585 (organisation_id), INDEX IDX_B8B4C6F3F6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, plan_date DATETIME NOT NULL, report LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, organisation_id INT DEFAULT NULL, request_id INT DEFAULT NULL, technician_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_D11814AB9E6B1585 (organisation_id), INDEX IDX_D11814AB427EB8A5 (request_id), INDEX IDX_D11814ABE6C5D496 (technician_id), INDEX IDX_D11814ABB03A8386 (created_by_id), INDEX IDX_D11814AB896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organisation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE request (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, priority VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, organisation_id INT DEFAULT NULL, site_id INT DEFAULT NULL, equipement_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_3B978F9F9E6B1585 (organisation_id), INDEX IDX_3B978F9FF6BD1646 (site_id), INDEX IDX_3B978F9F806F0F5C (equipement_id), INDEX IDX_3B978F9FB03A8386 (created_by_id), INDEX IDX_3B978F9F896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, adress VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, actif TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_694309E49E6B1585 (organisation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, organisation_id INT DEFAULT NULL, INDEX IDX_8D93D6499E6B1585 (organisation_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F39E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB427EB8A5 FOREIGN KEY (request_id) REFERENCES request (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814ABE6C5D496 FOREIGN KEY (technician_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814ABB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9F9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9FF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9F806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E49E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6499E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F39E6B1585');
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3F6BD1646');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB9E6B1585');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB427EB8A5');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814ABE6C5D496');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814ABB03A8386');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB896DBBDE');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9F9E6B1585');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FF6BD1646');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9F806F0F5C');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FB03A8386');
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9F896DBBDE');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E49E6B1585');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6499E6B1585');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE organisation');
        $this->addSql('DROP TABLE request');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
