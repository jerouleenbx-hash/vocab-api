<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303134720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE word (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, definition LONGTEXT NOT NULL, example_sentence LONGTEXT NOT NULL, difficulty SMALLINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C3F17511A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE word_progress (id INT AUTO_INCREMENT NOT NULL, repetition_level SMALLINT NOT NULL, success_streak SMALLINT NOT NULL, fail_count SMALLINT NOT NULL, next_review_at DATETIME NOT NULL, last_reviewed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, word_id INT NOT NULL, INDEX IDX_6BF2397A76ED395 (user_id), INDEX IDX_6BF2397E357438D (word_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE word ADD CONSTRAINT FK_C3F17511A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE word_progress ADD CONSTRAINT FK_6BF2397A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE word_progress ADD CONSTRAINT FK_6BF2397E357438D FOREIGN KEY (word_id) REFERENCES word (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE word DROP FOREIGN KEY FK_C3F17511A76ED395');
        $this->addSql('ALTER TABLE word_progress DROP FOREIGN KEY FK_6BF2397A76ED395');
        $this->addSql('ALTER TABLE word_progress DROP FOREIGN KEY FK_6BF2397E357438D');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE word');
        $this->addSql('DROP TABLE word_progress');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
