<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user, word and word_progress tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_USER_EMAIL (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE word (
                id INT AUTO_INCREMENT NOT NULL,
                word VARCHAR(255) NOT NULL,
                definition TEXT NOT NULL,
                example_sentence TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE word_progress (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                word_id INT NOT NULL,
                correct INT NOT NULL,
                last_seen_at DATETIME DEFAULT NULL,
                INDEX IDX_PROGRESS_USER (user_id),
                INDEX IDX_PROGRESS_WORD (word_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE word_progress ADD CONSTRAINT FK_PROGRESS_USER FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE word_progress ADD CONSTRAINT FK_PROGRESS_WORD FOREIGN KEY (word_id) REFERENCES word (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE word_progress');
        $this->addSql('DROP TABLE word');
        $this->addSql('DROP TABLE user');
    }
}