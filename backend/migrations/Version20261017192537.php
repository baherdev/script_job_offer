<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261017192537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer ADD match_score DOUBLE PRECISION DEFAULT NULL, ADD matched_keywords JSON DEFAULT NULL, ADD missing_keywords JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE search_query ADD CONSTRAINT FK_10887602A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_10887602A76ED395 ON search_query (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer DROP match_score, DROP matched_keywords, DROP missing_keywords');
        $this->addSql('ALTER TABLE search_query DROP FOREIGN KEY FK_10887602A76ED395');
        $this->addSql('DROP INDEX IDX_10887602A76ED395 ON search_query');
    }
}
