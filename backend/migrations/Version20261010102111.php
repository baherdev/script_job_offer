<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010102111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, salutation INT DEFAULT 0 NOT NULL, enterprise_id INT DEFAULT NULL, INDEX IDX_4C62E638A97D1AC3 (enterprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_offer_contact (contact_id INT NOT NULL, job_offer_id INT NOT NULL, INDEX IDX_46B47EFDE7A1254A (contact_id), INDEX IDX_46B47EFD3481D195 (job_offer_id), PRIMARY KEY (contact_id, job_offer_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id)');
        $this->addSql('ALTER TABLE job_offer_contact ADD CONSTRAINT FK_46B47EFDE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_contact ADD CONSTRAINT FK_46B47EFD3481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638A97D1AC3');
        $this->addSql('ALTER TABLE job_offer_contact DROP FOREIGN KEY FK_46B47EFDE7A1254A');
        $this->addSql('ALTER TABLE job_offer_contact DROP FOREIGN KEY FK_46B47EFD3481D195');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE job_offer_contact');
    }
}
