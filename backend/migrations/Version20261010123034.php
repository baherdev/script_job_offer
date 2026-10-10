<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010123034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer ADD source_id INT DEFAULT 0 NOT NULL, ADD minimum_salary INT DEFAULT NULL, ADD minimum_salary_currency INT DEFAULT 0 NOT NULL, ADD maximum_salary INT DEFAULT NULL, ADD maximum_salary_currency INT DEFAULT 0 NOT NULL, ADD presence_mode INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer DROP source_id, DROP minimum_salary, DROP minimum_salary_currency, DROP maximum_salary, DROP maximum_salary_currency, DROP presence_mode');
    }
}
