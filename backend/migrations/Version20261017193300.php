<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261017193300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link job offers to the search queries that found them';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_query_job_offer (search_query_id INT NOT NULL, job_offer_id INT NOT NULL, INDEX IDX_55AF390FFC3C42C (search_query_id), INDEX IDX_55AF3903481D195 (job_offer_id), PRIMARY KEY (search_query_id, job_offer_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE search_query_job_offer ADD CONSTRAINT FK_55AF390FFC3C42C FOREIGN KEY (search_query_id) REFERENCES search_query (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE search_query_job_offer ADD CONSTRAINT FK_55AF3903481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE search_query_job_offer DROP FOREIGN KEY FK_55AF390FFC3C42C');
        $this->addSql('ALTER TABLE search_query_job_offer DROP FOREIGN KEY FK_55AF3903481D195');
        $this->addSql('DROP TABLE search_query_job_offer');
    }
}
