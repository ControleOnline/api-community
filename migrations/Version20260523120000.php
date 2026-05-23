<?php
// ALEMAC // 2026/05/23 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add show_in_display flag to product_group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `product_group` ADD `show_in_display` TINYINT(1) DEFAULT 0 NOT NULL AFTER `active`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `product_group` DROP `show_in_display`');
    }
}
