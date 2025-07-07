<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250707112344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_ban (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_id INT NOT NULL, created_at DATETIME NOT NULL, reason VARCHAR(255) DEFAULT NULL, INDEX IDX_73311536A76ED395 (user_id), INDEX IDX_7331153671F7E88B (event_id), UNIQUE INDEX user_event_unique (user_id, event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_ban ADD CONSTRAINT FK_73311536A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_ban ADD CONSTRAINT FK_7331153671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE event_ban DROP FOREIGN KEY FK_73311536A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_ban DROP FOREIGN KEY FK_7331153671F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_ban
        SQL);
    }
}
