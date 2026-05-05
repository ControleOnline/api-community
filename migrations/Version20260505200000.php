<?php
// ALEMAC // 2026/05/05 20:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow product_group_product rows without product_group for main product feedstocks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_group_product MODIFY product_group_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM product_group_product WHERE product_group_id IS NULL');
        $this->addSql('ALTER TABLE product_group_product MODIFY product_group_id INT NOT NULL');
    }
}
