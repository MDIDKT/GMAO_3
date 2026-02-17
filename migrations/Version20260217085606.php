<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217085606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE user SET actif = 0 WHERE actif IS NULL');
        $this->addSql('UPDATE user SET updated_at = created_at WHERE updated_at IS NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORGANISATION_NOM ON organisation (nom)');
        $this->addSql('ALTER TABLE user CHANGE actif actif TINYINT DEFAULT 0 NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_USER_INVITATION_TOKEN ON user (invitation_token)');
        $this->addSql('CREATE INDEX IDX_USER_ORG_ACTIF ON user (organisation_id, actif)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_ORGANISATION_NOM ON organisation');
        $this->addSql('DROP INDEX IDX_USER_INVITATION_TOKEN ON user');
        $this->addSql('DROP INDEX IDX_USER_ORG_ACTIF ON user');
        $this->addSql('ALTER TABLE user CHANGE actif actif TINYINT NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }
}
