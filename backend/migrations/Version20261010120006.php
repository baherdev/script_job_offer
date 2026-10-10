<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010120006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_application (id INT AUTO_INCREMENT NOT NULL, application_status INT DEFAULT 0 NOT NULL, application_date DATE DEFAULT NULL, interview_date1 DATE DEFAULT NULL, interview_date2 DATE DEFAULT NULL, interview_date3 DATE DEFAULT NULL, rejection_date DATE DEFAULT NULL, cancellation_date DATE DEFAULT NULL, first_start_date DATE DEFAULT NULL, desired_salary_amount INT DEFAULT NULL, desired_salary_currency INT DEFAULT 0 NOT NULL, job_offer_id INT NOT NULL, user_id INT NOT NULL, cover_letter_id INT DEFAULT NULL, cv_id INT DEFAULT NULL, INDEX IDX_C737C6883481D195 (job_offer_id), INDEX IDX_C737C688A76ED395 (user_id), INDEX IDX_C737C688B944729C (cover_letter_id), INDEX IDX_C737C688CFE419E2 (cv_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_application_reference_file (job_application_id INT NOT NULL, file_reference_id INT NOT NULL, INDEX IDX_C215264EAC7A5A08 (job_application_id), INDEX IDX_C215264EB5DB6523 (file_reference_id), PRIMARY KEY (job_application_id, file_reference_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_application_diploma_file (job_application_id INT NOT NULL, file_reference_id INT NOT NULL, INDEX IDX_84059E32AC7A5A08 (job_application_id), INDEX IDX_84059E32B5DB6523 (file_reference_id), PRIMARY KEY (job_application_id, file_reference_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_application_additional_file (job_application_id INT NOT NULL, file_reference_id INT NOT NULL, INDEX IDX_F837262DAC7A5A08 (job_application_id), INDEX IDX_F837262DB5DB6523 (file_reference_id), PRIMARY KEY (job_application_id, file_reference_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688B944729C FOREIGN KEY (cover_letter_id) REFERENCES file_reference (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688CFE419E2 FOREIGN KEY (cv_id) REFERENCES file_reference (id)');
        $this->addSql('ALTER TABLE job_application_reference_file ADD CONSTRAINT FK_C215264EAC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_reference_file ADD CONSTRAINT FK_C215264EB5DB6523 FOREIGN KEY (file_reference_id) REFERENCES file_reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_diploma_file ADD CONSTRAINT FK_84059E32AC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_diploma_file ADD CONSTRAINT FK_84059E32B5DB6523 FOREIGN KEY (file_reference_id) REFERENCES file_reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_additional_file ADD CONSTRAINT FK_F837262DAC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_additional_file ADD CONSTRAINT FK_F837262DB5DB6523 FOREIGN KEY (file_reference_id) REFERENCES file_reference (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C6883481D195');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688A76ED395');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688B944729C');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688CFE419E2');
        $this->addSql('ALTER TABLE job_application_reference_file DROP FOREIGN KEY FK_C215264EAC7A5A08');
        $this->addSql('ALTER TABLE job_application_reference_file DROP FOREIGN KEY FK_C215264EB5DB6523');
        $this->addSql('ALTER TABLE job_application_diploma_file DROP FOREIGN KEY FK_84059E32AC7A5A08');
        $this->addSql('ALTER TABLE job_application_diploma_file DROP FOREIGN KEY FK_84059E32B5DB6523');
        $this->addSql('ALTER TABLE job_application_additional_file DROP FOREIGN KEY FK_F837262DAC7A5A08');
        $this->addSql('ALTER TABLE job_application_additional_file DROP FOREIGN KEY FK_F837262DB5DB6523');
        $this->addSql('DROP TABLE job_application');
        $this->addSql('DROP TABLE job_application_reference_file');
        $this->addSql('DROP TABLE job_application_diploma_file');
        $this->addSql('DROP TABLE job_application_additional_file');
    }
}
