<?php
// ALEMAC // 2026/05/08 12:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill missing Food99 order product groups from unique catalog links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE order_product op
            INNER JOIN orders o
                ON o.id = op.order_id
               AND o.app = 'Food99'
            INNER JOIN (
                SELECT
                    product_id,
                    product_child_id,
                    MIN(product_group_id) AS product_group_id,
                    COUNT(DISTINCT product_group_id) AS group_count
                FROM product_group_product
                WHERE active = 1
                  AND product_group_id IS NOT NULL
                GROUP BY product_id, product_child_id
                HAVING group_count = 1
            ) catalog_link
                ON catalog_link.product_id = op.parent_product_id
               AND catalog_link.product_child_id = op.product_id
            SET op.product_group_id = catalog_link.product_group_id
            WHERE op.product_group_id IS NULL
              AND op.order_product_id IS NOT NULL
              AND op.parent_product_id IS NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
