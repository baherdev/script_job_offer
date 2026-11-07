<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add monitored pages owned by a single user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE monitored_page (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(2048) NOT NULL, hash VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_7E0E7C27A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE monitored_page ADD CONSTRAINT FK_MONITORED_PAGE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE monitored_page DROP FOREIGN KEY FK_MONITORED_PAGE_USER');
        $this->addSql('DROP TABLE monitored_page');
    }
}
