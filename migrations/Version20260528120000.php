<?php
// ALEMAC // 2026/05/28 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000 extends AbstractMigration
{
    private const LOG_CLASS_PRODUCT_GROUP_PRODUCT = 'ControleOnline\\Entity\\ProductGroupProduct';

    public function getDescription(): string
    {
        return 'Backfill shared modifier parents, null shared product ids and replace product_group_product uniqueness';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('product')
            || !$this->tableExists('product_group')
            || !$this->tableExists('product_group_parent')
            || !$this->tableExists('product_group_product')
            || !$this->tableExists('log')
        ) {
            return;
        }

        $this->backfillProductGroupParents();
        $this->deduplicateSharedGroupProducts();
        $this->makeProductIdNullable();
        $this->nullSharedProductIds();
        $this->reshapeProductGroupProductKeys();
    }

    public function down(Schema $schema): void
    {
    }

    private function backfillProductGroupParents(): void
    {
        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO product_group_parent (product_group_id, parent_product_id, active)
            SELECT pg.id, pg.parent_product_id, pg.active
            FROM product_group pg
            INNER JOIN product parent ON parent.id = pg.parent_product_id
            WHERE pg.parent_product_id IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE product_group_parent pgp
            INNER JOIN (
                SELECT pg.id AS product_group_id, pg.parent_product_id, pg.active
                FROM product_group pg
                INNER JOIN product parent ON parent.id = pg.parent_product_id
                WHERE pg.parent_product_id IS NOT NULL
            ) source ON source.product_group_id = pgp.product_group_id
                AND source.parent_product_id = pgp.parent_product_id
            SET pgp.active = GREATEST(pgp.active, source.active)
        SQL);
    }

    private function deduplicateSharedGroupProducts(): void
    {
        $duplicates = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                pgp.product_group_id,
                pgp.product_type,
                pgp.product_child_id,
                pgp.quantity
            FROM product_group_product pgp
            WHERE pgp.product_group_id IS NOT NULL
              AND pgp.product_type IN ('component', 'package')
            GROUP BY pgp.product_group_id, pgp.product_type, pgp.product_child_id, pgp.quantity
            HAVING COUNT(*) > 1
            ORDER BY pgp.product_group_id ASC, pgp.product_type ASC, pgp.product_child_id ASC, pgp.quantity ASC
        SQL);

        foreach ($duplicates as $duplicateKey) {
            $rows = $this->connection->fetchAllAssociative(<<<'SQL'
                SELECT
                    id,
                    product_group_id,
                    product_id,
                    product_type,
                    product_child_id,
                    quantity,
                    price,
                    active,
                    show_in_parent_queue
                FROM product_group_product
                WHERE product_group_id = :productGroupId
                  AND product_type = :productType
                  AND product_child_id = :productChildId
                  AND quantity <=> :quantity
                ORDER BY active DESC, id ASC
            SQL, [
                'productGroupId' => $duplicateKey['product_group_id'],
                'productType' => $duplicateKey['product_type'],
                'productChildId' => $duplicateKey['product_child_id'],
                'quantity' => $duplicateKey['quantity'],
            ]);

            if (count($rows) < 2) {
                continue;
            }

            $canonical = array_shift($rows);
            if (!is_array($canonical)) {
                continue;
            }

            foreach ($rows as $duplicate) {
                if (!is_array($duplicate)) {
                    continue;
                }

                if ($this->rowsDiffer($canonical, $duplicate, ['price', 'active', 'show_in_parent_queue'])) {
                    $this->logConflict(
                        'product_group_product_shared_conflict',
                        self::LOG_CLASS_PRODUCT_GROUP_PRODUCT,
                        (int) $duplicate['id'],
                        [
                            'key' => [
                                'product_group_id' => isset($duplicateKey['product_group_id']) ? (int) $duplicateKey['product_group_id'] : null,
                                'product_type' => $duplicateKey['product_type'] ?? null,
                                'product_child_id' => isset($duplicateKey['product_child_id']) ? (int) $duplicateKey['product_child_id'] : null,
                                'quantity' => isset($duplicateKey['quantity']) ? (float) $duplicateKey['quantity'] : null,
                            ],
                            'canonical' => $this->normalizeRow($canonical),
                            'duplicate' => $this->normalizeRow($duplicate),
                        ]
                    );
                }

                $this->connection->executeStatement(
                    'DELETE FROM product_group_product WHERE id = :duplicateId',
                    ['duplicateId' => (int) $duplicate['id']]
                );
            }
        }
    }

    private function makeProductIdNullable(): void
    {
        if ($this->columnExists('product_group_product', 'product_id')) {
            $this->addSql('ALTER TABLE product_group_product MODIFY product_id INT(11) DEFAULT NULL');
        }
    }

    private function nullSharedProductIds(): void
    {
        $this->addSql(<<<'SQL'
            UPDATE product_group_product
            SET product_id = NULL
            WHERE product_group_id IS NOT NULL
              AND product_type IN ('component', 'package')
        SQL);
    }

    private function reshapeProductGroupProductKeys(): void
    {
        if (!$this->indexExists('product_group_product', 'product_group_product_group_lookup')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD INDEX product_group_product_group_lookup (
                    product_group_id,
                    product_type,
                    product_child_id,
                    quantity
                )
            SQL);
        }

        if (!$this->columnExists('product_group_product', 'shared_scope_group_id')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD COLUMN shared_scope_group_id INT(11) NOT NULL DEFAULT 0
            SQL);
        }

        if (!$this->columnExists('product_group_product', 'feedstock_scope_product_id')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD COLUMN feedstock_scope_product_id INT(11) NOT NULL DEFAULT 0
            SQL);
        }

        if (!$this->columnExists('product_group_product', 'quantity_scope')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD COLUMN quantity_scope DECIMAL(10,3) NOT NULL DEFAULT 0
            SQL);
        }

        $this->populateProductGroupProductScopeColumns();
        $this->syncProductGroupProductScopeTriggers();

        if (!$this->indexExists('product_group_product', 'product_group_product_identity_unique')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD UNIQUE INDEX product_group_product_identity_unique (
                    shared_scope_group_id,
                    feedstock_scope_product_id,
                    product_type,
                    product_child_id,
                    quantity_scope
                )
            SQL);
        }

        if ($this->indexExists('product_group_product', 'product_group')) {
            $this->addSql('ALTER TABLE product_group_product DROP INDEX product_group');
        }

        if (!$this->indexExists('product_group_product', 'product_group_product_feedstock_lookup')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group_product
                ADD INDEX product_group_product_feedstock_lookup (
                    product_id,
                    product_type,
                    product_child_id,
                    quantity
                )
            SQL);
        }
    }

    private function populateProductGroupProductScopeColumns(): void
    {
        $this->addSql(<<<'SQL'
            UPDATE product_group_product
            SET shared_scope_group_id = CASE
                    WHEN product_type IN ('component', 'package') THEN IFNULL(product_group_id, 0)
                    ELSE 0
                END,
                feedstock_scope_product_id = CASE
                    WHEN product_type = 'feedstock' THEN IFNULL(product_id, 0)
                    ELSE 0
                END,
                quantity_scope = IFNULL(quantity, 0)
        SQL);
    }

    private function syncProductGroupProductScopeTriggers(): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS product_group_product_before_insert');
        $this->addSql(<<<'SQL'
            CREATE TRIGGER product_group_product_before_insert
            BEFORE INSERT ON product_group_product
            FOR EACH ROW
            SET NEW.shared_scope_group_id = CASE
                    WHEN NEW.product_type IN ('component', 'package') THEN IFNULL(NEW.product_group_id, 0)
                    ELSE 0
                END,
                NEW.feedstock_scope_product_id = CASE
                    WHEN NEW.product_type = 'feedstock' THEN IFNULL(NEW.product_id, 0)
                    ELSE 0
                END,
                NEW.quantity_scope = IFNULL(NEW.quantity, 0)
        SQL);

        $this->addSql('DROP TRIGGER IF EXISTS product_group_product_before_update');
        $this->addSql(<<<'SQL'
            CREATE TRIGGER product_group_product_before_update
            BEFORE UPDATE ON product_group_product
            FOR EACH ROW
            SET NEW.shared_scope_group_id = CASE
                    WHEN NEW.product_type IN ('component', 'package') THEN IFNULL(NEW.product_group_id, 0)
                    ELSE 0
                END,
                NEW.feedstock_scope_product_id = CASE
                    WHEN NEW.product_type = 'feedstock' THEN IFNULL(NEW.product_id, 0)
                    ELSE 0
                END,
                NEW.quantity_scope = IFNULL(NEW.quantity, 0)
        SQL);
    }

    private function rowsDiffer(array $left, array $right, array $fields): bool
    {
        foreach ($fields as $field) {
            $leftValue = $left[$field] ?? null;
            $rightValue = $right[$field] ?? null;

            if ((string) $leftValue !== (string) $rightValue) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRow(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'product_group_id' => isset($row['product_group_id']) ? ($row['product_group_id'] !== null ? (int) $row['product_group_id'] : null) : null,
            'product_id' => isset($row['product_id']) ? ($row['product_id'] !== null ? (int) $row['product_id'] : null) : null,
            'product_type' => $row['product_type'] ?? null,
            'product_child_id' => isset($row['product_child_id']) ? ($row['product_child_id'] !== null ? (int) $row['product_child_id'] : null) : null,
            'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : null,
            'price' => isset($row['price']) ? (float) $row['price'] : null,
            'active' => isset($row['active']) ? (int) $row['active'] : null,
            'show_in_parent_queue' => isset($row['show_in_parent_queue']) ? (int) $row['show_in_parent_queue'] : null,
        ];
    }

    private function logConflict(string $action, string $class, ?int $row, array $object): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO log (created_at, user_id, type, `row`, action, class, object)
                VALUES (NOW(), NULL, :type, :rowId, :action, :class, :object)
            SQL,
            [
                'type' => 'migration',
                'rowId' => $row,
                'action' => $action,
                'class' => $class,
                'object' => json_encode($object, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ]
        );
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }
}
