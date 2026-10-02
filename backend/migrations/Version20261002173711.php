<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002173711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_offer_file_reference (job_offer_id INT NOT NULL, file_reference_id INT NOT NULL, INDEX IDX_26B4B8FA3481D195 (job_offer_id), INDEX IDX_26B4B8FAB5DB6523 (file_reference_id), PRIMARY KEY (job_offer_id, file_reference_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_offer_file_reference ADD CONSTRAINT FK_26B4B8FA3481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_file_reference ADD CONSTRAINT FK_26B4B8FAB5DB6523 FOREIGN KEY (file_reference_id) REFERENCES file_reference (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer_file_reference DROP FOREIGN KEY FK_26B4B8FA3481D195');
        $this->addSql('ALTER TABLE job_offer_file_reference DROP FOREIGN KEY FK_26B4B8FAB5DB6523');
        $this->addSql('DROP TABLE job_offer_file_reference');
    }
}
