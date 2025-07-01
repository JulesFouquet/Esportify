<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration corrigée pour éviter l'erreur de contrainte FK
 */
final class Version20250630191812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables user, event et dépendances avec contraintes FK dans le bon ordre';
    }

    public function up(Schema $schema): void
    {
        // 1. Création de la table user (doit être créée avant event)
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                pseudo VARCHAR(50) NOT NULL,
                roles JSON NOT NULL COMMENT '(DC2Type:json)',
                password VARCHAR(255) NOT NULL,
                date_inscription DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                is_verified TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // 2. Création de la table event
        $this->addSql(<<<'SQL'
            CREATE TABLE event (
                id INT AUTO_INCREMENT NOT NULL,
                organizer_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                start_date_time DATETIME NOT NULL,
                end_date_time DATETIME NOT NULL,
                max_players INT NOT NULL,
                is_approved TINYINT(1) NOT NULL,
                is_started TINYINT(1) NOT NULL,
                is_admin_approved TINYINT(1) NOT NULL,
                INDEX IDX_3BAE0AA7876C4DDA (organizer_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // 3. Création des autres tables
        $this->addSql(<<<'SQL'
            CREATE TABLE event_participants (
                event_id INT NOT NULL,
                user_id INT NOT NULL,
                INDEX IDX_9C7A7A6171F7E88B (event_id),
                INDEX IDX_9C7A7A61A76ED395 (user_id),
                PRIMARY KEY(event_id, user_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE image (
                id INT AUTO_INCREMENT NOT NULL,
                event_id INT DEFAULT NULL,
                path VARCHAR(255) NOT NULL,
                INDEX IDX_C53D045F71F7E88B (event_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE message (
                id INT AUTO_INCREMENT NOT NULL,
                author_id INT NOT NULL,
                event_id INT NOT NULL,
                content VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_B6BD307FF675F31B (author_id),
                INDEX IDX_B6BD307F71F7E88B (event_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE score (
                id INT AUTO_INCREMENT NOT NULL,
                player_id INT DEFAULT NULL,
                event_id INT DEFAULT NULL,
                points INT NOT NULL,
                INDEX IDX_3299375199E6F5DF (player_id),
                INDEX IDX_3299375171F7E88B (event_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
                id BIGINT AUTO_INCREMENT NOT NULL,
                body LONGTEXT NOT NULL,
                headers LONGTEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_75EA56E0FB7336F0 (queue_name),
                INDEX IDX_75EA56E0E3BD61CE (available_at),
                INDEX IDX_75EA56E016BA31DB (delivered_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // 4. Ajout des contraintes étrangères dans le bon ordre
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event_participants ADD CONSTRAINT FK_9C7A7A6171F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_participants ADD CONSTRAINT FK_9C7A7A61A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_3299375199E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_3299375171F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des contraintes FK
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7876C4DDA');
        $this->addSql('ALTER TABLE event_participants DROP FOREIGN KEY FK_9C7A7A6171F7E88B');
        $this->addSql('ALTER TABLE event_participants DROP FOREIGN KEY FK_9C7A7A61A76ED395');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F71F7E88B');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F71F7E88B');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_3299375199E6F5DF');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_3299375171F7E88B');

        // Suppression des tables
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_participants');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
