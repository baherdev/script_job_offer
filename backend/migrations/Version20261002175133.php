<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002175133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_offer_contact (job_offer_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_46B47EFD3481D195 (job_offer_id), INDEX IDX_46B47EFDE7A1254A (contact_id), PRIMARY KEY (job_offer_id, contact_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_offer_contact ADD CONSTRAINT FK_46B47EFD3481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_contact ADD CONSTRAINT FK_46B47EFDE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer ADD entreprise_id INT NOT NULL');
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE INDEX IDX_288A3A4EA4AEAFEA ON job_offer (entreprise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer_contact DROP FOREIGN KEY FK_46B47EFD3481D195');
        $this->addSql('ALTER TABLE job_offer_contact DROP FOREIGN KEY FK_46B47EFDE7A1254A');
        $this->addSql('DROP TABLE job_offer_contact');
        $this->addSql('ALTER TABLE job_offer DROP FOREIGN KEY FK_288A3A4EA4AEAFEA');
        $this->addSql('DROP INDEX IDX_288A3A4EA4AEAFEA ON job_offer');
        $this->addSql('ALTER TABLE job_offer DROP entreprise_id');
    }
}
