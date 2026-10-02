<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002173010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_offer (id INT AUTO_INCREMENT NOT NULL, source_id INT NOT NULL, original_link LONGTEXT NOT NULL, publication_date DATE NOT NULL, minimum_salary INT NOT NULL, maximum_salary INT NOT NULL, location LONGTEXT NOT NULL, presence_mode INT NOT NULL, position_title LONGTEXT NOT NULL, description LONGTEXT NOT NULL, application_status INT NOT NULL, application_date DATE DEFAULT NULL, rejection_date DATE DEFAULT NULL, interview_date1 DATE DEFAULT NULL, interview_date2 DATE DEFAULT NULL, interview_date3 DATE DEFAULT NULL, application_validation_date DATE DEFAULT NULL, cancellation_date DATE DEFAULT NULL, cover_letter LONGTEXT DEFAULT NULL, desired_salary INT DEFAULT NULL, availability_date DATE DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE job_offer');
    }
}
