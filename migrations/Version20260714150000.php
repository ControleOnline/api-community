<?php
// ALEMAC // 2026/07/14 15:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate legacy people image columns into people_media and drop deprecated schema';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('people') ||
            !$this->tableExists('people_media') ||
            !$this->tableExists('media_types')
        ) {
            return;
        }

        if ($this->columnExists('people', 'image_id')) {
            $this->addSql(
                "INSERT IGNORE INTO people_media (people_id, file_id, media_type_id)
                 SELECT p.id, p.image_id, mt.id
                 FROM people p
                 INNER JOIN media_types mt
                   ON mt.type = CASE WHEN p.people_type = 'F' THEN 'avatar' ELSE 'logo' END
                 WHERE p.image_id IS NOT NULL"
            );
        }

        if ($this->columnExists('people', 'alternative_image')) {
            $this->addSql(
                "INSERT IGNORE INTO people_media (people_id, file_id, media_type_id)
                 SELECT p.id, p.alternative_image, mt.id
                 FROM people p
                 INNER JOIN media_types mt
                   ON mt.type = CASE WHEN p.people_type = 'F' THEN 'avatar' ELSE 'icon' END
                 WHERE p.alternative_image IS NOT NULL
                 AND (p.people_type <> 'F' OR p.image_id IS NULL)"
            );
        }

        if ($this->columnExists('people', 'background_image')) {
            $this->addSql(
                "INSERT IGNORE INTO people_media (people_id, file_id, media_type_id)
                 SELECT p.id, p.background_image, mt.id
                 FROM people p
                 INNER JOIN media_types mt
                   ON mt.type = 'background'
                 WHERE p.background_image IS NOT NULL"
            );
        }

        $this->dropForeignKeysForColumns('people', [
            'image_id',
            'background_image',
            'alternative_image',
        ]);
        $this->dropIndexesForColumns('people', [
            'image_id',
            'background_image',
            'alternative_image',
        ]);

        $this->dropColumnIfExists('people', 'image_id');
        $this->dropColumnIfExists('people', 'background_image');
        $this->dropColumnIfExists('people', 'alternative_image');
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('people')) {
            return;
        }

        $this->ensureColumn('people', 'image_id', 'INT DEFAULT NULL');
        $this->ensureColumn('people', 'background_image', 'INT DEFAULT NULL');
        $this->ensureColumn('people', 'alternative_image', 'INT DEFAULT NULL');

        $this->ensureIndex('people', 'image_id', 'image_id');
        $this->ensureIndex('people', 'alternative_image', 'background_image');
        $this->ensureIndex('people', 'alternative_image_2', 'alternative_image');

        $this->ensureForeignKey('people', 'people_ibfk_1', 'image_id', 'files', 'id');
        $this->ensureForeignKey('people', 'people_ibfk_3', 'background_image', 'files', 'id');
        $this->ensureForeignKey('people', 'people_ibfk_4', 'alternative_image', 'files', 'id');
    }

    private function ensureColumn(string $tableName, string $columnName, string $definition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
    }

    private function ensureIndex(string $tableName, string $indexName, string $columnName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD INDEX %s (%s)', $tableName, $indexName, $columnName));
    }

    private function ensureForeignKey(
        string $tableName,
        string $constraintName,
        string $columnName,
        string $referencedTable,
        string $referencedColumn
    ): void {
        if ($this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE SET NULL ON UPDATE CASCADE',
            $tableName,
            $constraintName,
            $columnName,
            $referencedTable,
            $referencedColumn
        ));
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $columnName));
    }

    /**
     * @param array<int, string> $columnNames
     */
    private function dropIndexesForColumns(string $tableName, array $columnNames): void
    {
        foreach ($this->findIndexesForColumns($tableName, $columnNames) as $indexName) {
            $this->addSql(sprintf('ALTER TABLE %s DROP INDEX %s', $tableName, $indexName));
        }
    }

    /**
     * @param array<int, string> $columnNames
     * @return array<int, string>
     */
    private function findIndexesForColumns(string $tableName, array $columnNames): array
    {
        if ($columnNames === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));
        $sql = sprintf(
            'SELECT DISTINCT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME IN (%s)
             AND INDEX_NAME <> \'PRIMARY\'',
            $placeholders
        );

        $rows = $this->connection->fetchFirstColumn($sql, array_merge([$tableName], array_values($columnNames)));

        return array_values(array_filter(array_unique(array_map(
            static fn(mixed $indexName): string => (string) $indexName,
            $rows
        ))));
    }

    /**
     * @param array<int, string> $columnNames
     */
    private function dropForeignKeysForColumns(string $tableName, array $columnNames): void
    {
        foreach ($this->findForeignKeysForColumns($tableName, $columnNames) as $constraintName) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableName, $constraintName));
        }
    }

    /**
     * @param array<int, string> $columnNames
     * @return array<int, string>
     */
    private function findForeignKeysForColumns(string $tableName, array $columnNames): array
    {
        if ($columnNames === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));
        $sql = sprintf(
            'SELECT DISTINCT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME IN (%s)
             AND REFERENCED_TABLE_NAME IS NOT NULL',
            $placeholders
        );

        $rows = $this->connection->fetchFirstColumn($sql, array_merge([$tableName], array_values($columnNames)));

        return array_values(array_filter(array_unique(array_map(
            static fn(mixed $constraintName): string => (string) $constraintName,
            $rows
        ))));
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

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$tableName, $constraintName]
        ) > 0;
    }
}
