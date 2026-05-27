<?php
// ALEMAC // 2026/05/29 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company to product_group and drop legacy parent_product_id';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('product_group')
            || !$this->tableExists('product_group_parent')
            || !$this->tableExists('product')
            || !$this->tableExists('people')
        ) {
            return;
        }

        $hasCompanyColumn = $this->columnExists('product_group', 'company_id');
        $hasLegacyParentColumn = $this->columnExists('product_group', 'parent_product_id');

        if (!$hasCompanyColumn) {
            $this->addCompanyColumn();
        }
        $this->backfillCompanyColumn();
        $this->makeCompanyColumnRequired();
        $this->addCompanyForeignKey();
        $this->dropLegacyParentProductColumn($hasLegacyParentColumn);
    }

    public function down(Schema $schema): void
    {
    }

    private function addCompanyColumn(): void
    {
        if (!$this->columnExists('product_group', 'company_id')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group
                ADD COLUMN company_id INT(11) DEFAULT NULL
            SQL);
        }
    }

    private function backfillCompanyColumn(): void
    {
        $this->addSql(<<<'SQL'
            UPDATE product_group pg
            INNER JOIN (
                SELECT
                    pgp.product_group_id,
                    MIN(parent.company_id) AS company_id
                FROM product_group_parent pgp
                INNER JOIN product parent
                    ON parent.id = pgp.parent_product_id
                GROUP BY pgp.product_group_id
            ) source ON source.product_group_id = pg.id
            SET pg.company_id = source.company_id
            WHERE pg.company_id IS NULL
        SQL);
    }

    private function makeCompanyColumnRequired(): void
    {
        $this->addSql('ALTER TABLE product_group MODIFY company_id INT(11) NOT NULL');
    }

    private function addCompanyForeignKey(): void
    {
        if (!$this->indexExists('product_group', 'product_group_company_id')) {
            $this->addSql('ALTER TABLE product_group ADD INDEX product_group_company_id (company_id)');
        }

        if (!$this->foreignKeyExists('product_group', 'product_group_company_id_fk')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE product_group
                ADD CONSTRAINT product_group_company_id_fk
                FOREIGN KEY (company_id) REFERENCES people (id)
                ON UPDATE CASCADE
            SQL);
        }
    }

    private function dropLegacyParentProductColumn(bool $hasLegacyParentColumn): void
    {
        if (!$hasLegacyParentColumn) {
            return;
        }

        if ($this->foreignKeyExists('product_group', 'product_group_ibfk_1')) {
            $this->addSql('ALTER TABLE product_group DROP FOREIGN KEY product_group_ibfk_1');
        }

        if ($this->indexExists('product_group', 'product_parent_id')) {
            $this->addSql('ALTER TABLE product_group DROP INDEX product_parent_id');
        }

        $this->addSql('ALTER TABLE product_group DROP COLUMN parent_product_id');
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$tableName, $foreignKeyName]
        ) > 0;
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
