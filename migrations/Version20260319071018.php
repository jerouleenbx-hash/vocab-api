<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319071018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        $this->addSql('DROP INDEX idx1 ON word');
        $this->addSql('DROP INDEX idx2 ON word');
        $this->addSql('ALTER TABLE word CHANGE tags tags VARCHAR(2000) DEFAULT NULL');
        $this->addSql('ALTER TABLE word_progress ADD stability DOUBLE PRECISION NOT NULL, ADD difficulty DOUBLE PRECISION NOT NULL, ADD last_review DATETIME DEFAULT NULL, ADD next_review DATETIME DEFAULT NULL, ADD reps INT NOT NULL, ADD lapses INT NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE word CHANGE tags tags VARCHAR(2000) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX idx1 ON word (difficulty(768))');
        $this->addSql('CREATE INDEX idx2 ON word (tags(768))');
        $this->addSql('ALTER TABLE word_progress DROP stability, DROP difficulty, DROP last_review, DROP next_review, DROP reps, DROP lapses');
    }
}
