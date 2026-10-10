<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010094203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_query_interested_user (search_query_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_985E262AFFC3C42C (search_query_id), INDEX IDX_985E262AA76ED395 (user_id), PRIMARY KEY (search_query_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE search_query_interested_user ADD CONSTRAINT FK_985E262AFFC3C42C FOREIGN KEY (search_query_id) REFERENCES search_query (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE search_query_interested_user ADD CONSTRAINT FK_985E262AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_query_interested_user DROP FOREIGN KEY FK_985E262AFFC3C42C');
        $this->addSql('ALTER TABLE search_query_interested_user DROP FOREIGN KEY FK_985E262AA76ED395');
        $this->addSql('DROP TABLE search_query_interested_user');
    }
}
