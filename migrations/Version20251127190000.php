<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127190000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE people_document (
            id INT AUTO_INCREMENT NOT NULL,
            people_id INT NOT NULL,
            documentType_id INT NOT NULL,
            INDEX IDX_PEOPLE_ID (people_id),
            INDEX IDX_DOCUMENTTYPE_ID (documentType_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE people_document');
    }
}
