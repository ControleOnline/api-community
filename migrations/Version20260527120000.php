<?php
// ALEMAC // 2026/05/27 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527120000 extends AbstractMigration
{
    private const SHARED_MODEL_COMPANY_ID = 3;
    private const LOG_CLASS_PRODUCT_GROUP = 'ControleOnline\\Entity\\ProductGroup';
    private const LOG_CLASS_PRODUCT_GROUP_PRODUCT = 'ControleOnline\\Entity\\ProductGroupProduct';

    public function getDescription(): string
    {
        return 'Consolidate company 3 shared product groups and deduplicate shared catalog items';
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

        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO product_group_parent (product_group_id, parent_product_id, active)
            SELECT pg.id, pg.parent_product_id, pg.active
            FROM product_group pg
            INNER JOIN product parent ON parent.id = pg.parent_product_id
            WHERE parent.company_id = :companyId
              AND pg.parent_product_id IS NOT NULL
        SQL, [
            'companyId' => self::SHARED_MODEL_COMPANY_ID,
        ]);

        $this->addSql(<<<'SQL'
            UPDATE product_group_parent pgp
            INNER JOIN (
                SELECT pg.id AS product_group_id, pg.parent_product_id, pg.active
                FROM product_group pg
                INNER JOIN product parent ON parent.id = pg.parent_product_id
                WHERE parent.company_id = :companyId
                  AND pg.parent_product_id IS NOT NULL
            ) source ON source.product_group_id = pgp.product_group_id
                AND source.parent_product_id = pgp.parent_product_id
            SET pgp.active = GREATEST(pgp.active, source.active)
        SQL, [
            'companyId' => self::SHARED_MODEL_COMPANY_ID,
        ]);

        $this->consolidateSharedGroups();
        $this->deduplicateProductGroupProducts();
    }

    public function down(Schema $schema): void
    {
    }

    private function consolidateSharedGroups(): void
    {
        $groups = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                pg.id,
                pg.product_group,
                pg.parent_product_id,
                pg.price_calculation,
                pg.required,
                pg.minimum,
                pg.maximum,
                pg.group_order,
                pg.active,
                pg.show_in_display
            FROM product_group pg
            INNER JOIN product parent ON parent.id = pg.parent_product_id
            WHERE parent.company_id = :companyId
            ORDER BY pg.product_group ASC, pg.active DESC, pg.id ASC
        SQL, [
            'companyId' => self::SHARED_MODEL_COMPANY_ID,
        ]);

        $groupsByName = [];
        foreach ($groups as $group) {
            $groupsByName[(string) $group['product_group']][] = $group;
        }

        foreach ($groupsByName as $groupRows) {
            if (count($groupRows) < 2) {
                continue;
            }

            $canonical = array_shift($groupRows);
            if (!is_array($canonical)) {
                continue;
            }

            $canonicalId = (int) $canonical['id'];

            foreach ($groupRows as $duplicate) {
                if (!is_array($duplicate)) {
                    continue;
                }

                $duplicateId = (int) $duplicate['id'];
                $this->logGroupConflictIfNeeded($canonical, $duplicate);
                $this->mergeGroupParentLinks($canonicalId, $duplicateId);
                $this->moveGroupProducts($canonicalId, $duplicateId);

                $this->connection->executeStatement(
                    'DELETE FROM product_group WHERE id = :duplicateId',
                    ['duplicateId' => $duplicateId]
                );
            }
        }
    }

    private function deduplicateProductGroupProducts(): void
    {
        $groupDuplicates = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                pgp.product_group_id,
                pgp.product_type,
                pgp.product_child_id
            FROM product_group_product pgp
            INNER JOIN product_group pg ON pg.id = pgp.product_group_id
            INNER JOIN product parent ON parent.id = pg.parent_product_id
            WHERE parent.company_id = :companyId
            GROUP BY pgp.product_group_id, pgp.product_type, pgp.product_child_id
            HAVING COUNT(*) > 1
            ORDER BY pgp.product_group_id ASC, pgp.product_type ASC, pgp.product_child_id ASC
        SQL, [
            'companyId' => self::SHARED_MODEL_COMPANY_ID,
        ]);

        foreach ($groupDuplicates as $duplicateKey) {
            $this->pruneProductGroupProductDuplicates(
                [
                    'product_group_id' => $duplicateKey['product_group_id'],
                    'product_id' => null,
                    'product_type' => $duplicateKey['product_type'],
                    'product_child_id' => $duplicateKey['product_child_id'],
                ],
                true
            );
        }

        $feedstockDuplicates = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                pgp.product_id,
                pgp.product_type,
                pgp.product_child_id
            FROM product_group_product pgp
            INNER JOIN product parent ON parent.id = pgp.product_id
            WHERE pgp.product_group_id IS NULL
              AND parent.company_id = :companyId
            GROUP BY pgp.product_id, pgp.product_type, pgp.product_child_id
            HAVING COUNT(*) > 1
            ORDER BY pgp.product_id ASC, pgp.product_type ASC, pgp.product_child_id ASC
        SQL, [
            'companyId' => self::SHARED_MODEL_COMPANY_ID,
        ]);

        foreach ($feedstockDuplicates as $duplicateKey) {
            $this->pruneProductGroupProductDuplicates(
                [
                    'product_group_id' => null,
                    'product_id' => $duplicateKey['product_id'],
                    'product_type' => $duplicateKey['product_type'],
                    'product_child_id' => $duplicateKey['product_child_id'],
                ],
                false
            );
        }
    }

    private function pruneProductGroupProductDuplicates(array $duplicateKey, bool $hasGroup): void
    {
        $rows = $hasGroup
            ? $this->connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        id,
                        product_id,
                        product_group_id,
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
                    ORDER BY active DESC, id ASC
                SQL,
                [
                    'productGroupId' => $duplicateKey['product_group_id'],
                    'productType' => $duplicateKey['product_type'],
                    'productChildId' => $duplicateKey['product_child_id'],
                ]
            )
            : $this->connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT
                        id,
                        product_id,
                        product_group_id,
                        product_type,
                        product_child_id,
                        quantity,
                        price,
                        active,
                        show_in_parent_queue
                    FROM product_group_product
                    WHERE product_group_id IS NULL
                      AND product_id = :productId
                      AND product_type = :productType
                      AND product_child_id = :productChildId
                    ORDER BY active DESC, id ASC
                SQL,
                [
                    'productId' => $duplicateKey['product_id'],
                    'productType' => $duplicateKey['product_type'],
                    'productChildId' => $duplicateKey['product_child_id'],
                ]
            );

        if (count($rows) < 2) {
            return;
        }

        $canonical = array_shift($rows);
        if (!is_array($canonical)) {
            return;
        }

        foreach ($rows as $duplicate) {
            if (!is_array($duplicate)) {
                continue;
            }

            if ($this->rowsDiffer($canonical, $duplicate, ['quantity', 'price', 'active', 'show_in_parent_queue'])) {
                $this->logConflict(
                    'product_group_product_conflict',
                    self::LOG_CLASS_PRODUCT_GROUP_PRODUCT,
                    (int) $duplicate['id'],
                    [
                        'key' => $duplicateKey,
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

    private function mergeGroupParentLinks(int $canonicalId, int $duplicateId): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT IGNORE INTO product_group_parent (product_group_id, parent_product_id, active)
                SELECT :canonicalId, parent_product_id, active
                FROM product_group_parent
                WHERE product_group_id = :duplicateId
            SQL,
            [
                'canonicalId' => $canonicalId,
                'duplicateId' => $duplicateId,
            ]
        );

        $this->connection->executeStatement(
            <<<'SQL'
                UPDATE product_group_parent canonical
                INNER JOIN (
                    SELECT parent_product_id, active
                    FROM product_group_parent
                    WHERE product_group_id = :duplicateId
                ) source ON source.parent_product_id = canonical.parent_product_id
                SET canonical.active = GREATEST(canonical.active, source.active)
                WHERE canonical.product_group_id = :canonicalId
            SQL,
            [
                'canonicalId' => $canonicalId,
                'duplicateId' => $duplicateId,
            ]
        );
    }

    private function moveGroupProducts(int $canonicalId, int $duplicateId): void
    {
        $this->connection->executeStatement(
            'UPDATE product_group_product SET product_group_id = :canonicalId WHERE product_group_id = :duplicateId',
            [
                'canonicalId' => $canonicalId,
                'duplicateId' => $duplicateId,
            ]
        );
    }

    private function logGroupConflictIfNeeded(array $canonical, array $duplicate): void
    {
        $fields = [
            'price_calculation',
            'required',
            'minimum',
            'maximum',
            'group_order',
            'active',
            'show_in_display',
        ];

        if (!$this->rowsDiffer($canonical, $duplicate, $fields)) {
            return;
        }

        $this->logConflict(
            'product_group_conflict',
            self::LOG_CLASS_PRODUCT_GROUP,
            (int) $duplicate['id'],
            [
                'canonical' => $this->normalizeRow($canonical),
                'duplicate' => $this->normalizeRow($duplicate),
            ]
        );
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
            'product_group' => $row['product_group'] ?? null,
            'parent_product_id' => isset($row['parent_product_id']) ? ($row['parent_product_id'] !== null ? (int) $row['parent_product_id'] : null) : null,
            'price_calculation' => $row['price_calculation'] ?? null,
            'required' => isset($row['required']) ? (int) $row['required'] : null,
            'minimum' => isset($row['minimum']) ? (int) $row['minimum'] : null,
            'maximum' => isset($row['maximum']) ? (int) $row['maximum'] : null,
            'group_order' => isset($row['group_order']) ? (int) $row['group_order'] : null,
            'show_in_display' => isset($row['show_in_display']) ? (int) $row['show_in_display'] : null,
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
}
