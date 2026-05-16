<?php
// ALEMAC // 2026/05/15 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand people_link.link_type set with courier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `people_link` CHANGE `link_type` `link_type` SET('prospect','employee','client','provider','franchisee','professor','family','salesman','owner','sellers-client','director','manager','admin','courier') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `people_link` CHANGE `link_type` `link_type` SET('prospect','employee','client','provider','franchisee','professor','family','salesman','owner','sellers-client','director','manager','admin') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL");
    }
}
