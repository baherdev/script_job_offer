<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031210500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a unique optional logo on enterprise, mapped to file_reference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enterprise ADD logo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enterprise ADD CONSTRAINT FK_B1B36A03F98F144A FOREIGN KEY (logo_id) REFERENCES file_reference (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B1B36A03F98F144A ON enterprise (logo_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enterprise DROP FOREIGN KEY FK_B1B36A03F98F144A');
        $this->addSql('DROP INDEX UNIQ_B1B36A03F98F144A ON enterprise');
        $this->addSql('ALTER TABLE enterprise DROP logo_id');
    }
}
