<?php
// ALEMAC // 2026/05/05 10:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add queue group visibility flags';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_group_parent ADD show_in_queue TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE order_product ADD show_product_group_in_queue TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_product DROP show_product_group_in_queue');
        $this->addSql('ALTER TABLE product_group_parent DROP show_in_queue');
    }
}
