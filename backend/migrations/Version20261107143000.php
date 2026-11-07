<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow a monitored page to be created without a hash';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE monitored_page CHANGE hash hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE monitored_page CHANGE hash hash VARCHAR(255) NOT NULL');
    }
}
