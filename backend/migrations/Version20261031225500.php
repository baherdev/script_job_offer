<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031225500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the many-to-many blacklist between users and enterprises';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_black_listed_enterprise (user_id INT NOT NULL, enterprise_id INT NOT NULL, INDEX IDX_3E5DB510A76ED395 (user_id), INDEX IDX_3E5DB510A97D1AC3 (enterprise_id), PRIMARY KEY (user_id, enterprise_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_black_listed_enterprise ADD CONSTRAINT FK_3E5DB510A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_black_listed_enterprise ADD CONSTRAINT FK_3E5DB510A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_black_listed_enterprise DROP FOREIGN KEY FK_3E5DB510A76ED395');
        $this->addSql('ALTER TABLE user_black_listed_enterprise DROP FOREIGN KEY FK_3E5DB510A97D1AC3');
        $this->addSql('DROP TABLE user_black_listed_enterprise');
    }
}
