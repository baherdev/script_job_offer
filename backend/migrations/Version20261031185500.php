<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031185500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop job_offer.company; the name is stored on enterprise.label';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer DROP company');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE job_offer ADD company VARCHAR(255) NOT NULL DEFAULT ''");
    }
}
