<?php
// ALEMAC // 2026/05/04 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product_group_parent association table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_group_parent (id INT AUTO_INCREMENT NOT NULL, product_group_id INT NOT NULL, parent_product_id INT NOT NULL, active TINYINT(1) DEFAULT 1 NOT NULL, INDEX product_group_parent_product_id (parent_product_id), INDEX IDX_1BB5D390198093C5 (product_group_id), UNIQUE INDEX product_group_parent_unique (product_group_id, parent_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO product_group_parent (product_group_id, parent_product_id, active) SELECT id, parent_product_id, active FROM product_group WHERE parent_product_id IS NOT NULL ON DUPLICATE KEY UPDATE active = VALUES(active)');
        $this->addSql('ALTER TABLE product_group_parent ADD CONSTRAINT FK_1BB5D390198093C5 FOREIGN KEY (product_group_id) REFERENCES product_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_group_parent ADD CONSTRAINT FK_1BB5D390727ACA70 FOREIGN KEY (parent_product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_group_parent DROP FOREIGN KEY FK_1BB5D390198093C5');
        $this->addSql('ALTER TABLE product_group_parent DROP FOREIGN KEY FK_1BB5D390727ACA70');
        $this->addSql('DROP TABLE product_group_parent');
    }
}
