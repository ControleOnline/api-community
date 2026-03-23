<?php
// ALEMAC // 2026/03/22 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand people_link.link_type enum with owner, director and manager';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE people_link MODIFY link_type ENUM('employee','owner','director','manager','client','provider','franchisee') NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE people_link MODIFY link_type ENUM('employee','client','provider','franchisee') NOT NULL");
    }
}