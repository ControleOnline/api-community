<?php
// ALEMAC // 2026/06/06 17:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606170000 extends AbstractMigration
{
    private const ORDER_PRODUCT_CONTEXT = 'order_product';
    private const OPEN_STATUS = [
        'status' => 'open',
        'real_status' => 'open',
        'color' => '#334155',
    ];
    private const CHECKED_STATUS = [
        'status' => 'conferido',
        'real_status' => 'conferido',
        'color' => '#16A34A',
    ];

    public function getDescription(): string
    {
        return 'Add order_product status tracking with conferido conference state';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('status') || !$this->tableExists('order_product')) {
            return;
        }

        $this->seedOrderProductStatuses();

        if (!$this->columnExists('order_product', 'status_id')) {
            $this->addSql('ALTER TABLE order_product ADD COLUMN status_id INT(11) DEFAULT NULL');
        }

        $this->backfillOpenStatus();
        $this->addStatusIndex();
        $this->makeStatusRequired();
        $this->addStatusForeignKey();
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('order_product')) {
            if ($this->foreignKeyExists('order_product', 'order_product_status_id_fk')) {
                $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY order_product_status_id_fk');
            }

            if ($this->indexExists('order_product', 'status_id')) {
                $this->addSql('ALTER TABLE order_product DROP INDEX status_id');
            }

            if ($this->columnExists('order_product', 'status_id')) {
                $this->addSql('ALTER TABLE order_product DROP COLUMN status_id');
            }
        }

        if ($this->tableExists('status')) {
            $this->addSql(
                "DELETE FROM status
                 WHERE context = :context
                 AND status IN (:openStatus, :checkedStatus)",
                [
                    'context' => self::ORDER_PRODUCT_CONTEXT,
                    'openStatus' => self::OPEN_STATUS['status'],
                    'checkedStatus' => self::CHECKED_STATUS['status'],
                ]
            );
        }
    }

    private function seedOrderProductStatuses(): void
    {
        foreach ([self::OPEN_STATUS, self::CHECKED_STATUS] as $status) {
            $this->addSql(
                'INSERT INTO status (status, real_status, visibility, notify, system, color, context)
                 VALUES (:status, :realStatus, 1, 1, 0, :color, :context)
                 ON DUPLICATE KEY UPDATE
                    real_status = VALUES(real_status),
                    visibility = VALUES(visibility),
                    notify = VALUES(notify),
                    system = VALUES(system),
                    color = VALUES(color)',
                [
                    'status' => $status['status'],
                    'realStatus' => $status['real_status'],
                    'color' => $status['color'],
                    'context' => self::ORDER_PRODUCT_CONTEXT,
                ]
            );
        }
    }

    private function backfillOpenStatus(): void
    {
        $this->addSql(
            'UPDATE order_product op
             INNER JOIN status st
                ON st.status = :status
               AND st.real_status = :realStatus
               AND st.context = :context
             SET op.status_id = st.id
             WHERE op.status_id IS NULL',
            [
                'status' => self::OPEN_STATUS['status'],
                'realStatus' => self::OPEN_STATUS['real_status'],
                'context' => self::ORDER_PRODUCT_CONTEXT,
            ]
        );
    }

    private function addStatusIndex(): void
    {
        if (!$this->indexExists('order_product', 'status_id')) {
            $this->addSql('ALTER TABLE order_product ADD INDEX status_id (status_id)');
        }
    }

    private function makeStatusRequired(): void
    {
        $this->addSql('ALTER TABLE order_product MODIFY status_id INT(11) NOT NULL');
    }

    private function addStatusForeignKey(): void
    {
        if (!$this->foreignKeyExists('order_product', 'order_product_status_id_fk')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE order_product
                ADD CONSTRAINT order_product_status_id_fk
                FOREIGN KEY (status_id) REFERENCES status (id)
                ON UPDATE CASCADE
            SQL);
        }
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

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$tableName, $foreignKeyName]
        ) > 0;
    }
}
