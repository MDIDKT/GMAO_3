<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223110942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, taille INT NOT NULL, type VARCHAR(255) NOT NULL, legende VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, demande_id INT DEFAULT NULL, upload_par_id INT NOT NULL, INDEX IDX_14B7841880E95E18 (demande_id), INDEX IDX_14B78418CBA5D612 (upload_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841880E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418CBA5D612 FOREIGN KEY (upload_par_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B7841880E95E18');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418CBA5D612');
        $this->addSql('DROP TABLE photo');
    }
}
