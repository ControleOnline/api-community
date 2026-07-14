<?php
// ALEMAC // 2026/07/14 14:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add RH category relation columns to employee_profile using job_title_id style names';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('employee_profile')) {
            return;
        }

        $this->ensureColumn('employee_profile', 'job_title_id', 'INT DEFAULT NULL');
        $this->ensureColumn('employee_profile', 'job_function_id', 'INT DEFAULT NULL');
        $this->ensureColumn('employee_profile', 'department_id', 'INT DEFAULT NULL');

        $this->ensureIndex('employee_profile', 'employee_profile_job_title_idx', 'job_title_id');
        $this->ensureIndex('employee_profile', 'employee_profile_job_function_idx', 'job_function_id');
        $this->ensureIndex('employee_profile', 'employee_profile_department_idx', 'department_id');

        if (!$this->tableExists('category')) {
            return;
        }

        $this->ensureForeignKey(
            'employee_profile',
            'FK_EMPLOYEE_PROFILE_JOB_TITLE',
            'job_title_id',
            'category',
            'id'
        );
        $this->ensureForeignKey(
            'employee_profile',
            'FK_EMPLOYEE_PROFILE_JOB_FUNCTION',
            'job_function_id',
            'category',
            'id'
        );
        $this->ensureForeignKey(
            'employee_profile',
            'FK_EMPLOYEE_PROFILE_DEPARTMENT',
            'department_id',
            'category',
            'id'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('employee_profile')) {
            return;
        }

        $this->dropForeignKeyIfExists('employee_profile', 'FK_EMPLOYEE_PROFILE_DEPARTMENT');
        $this->dropForeignKeyIfExists('employee_profile', 'FK_EMPLOYEE_PROFILE_JOB_FUNCTION');
        $this->dropForeignKeyIfExists('employee_profile', 'FK_EMPLOYEE_PROFILE_JOB_TITLE');

        $this->dropIndexIfExists('employee_profile', 'employee_profile_department_idx');
        $this->dropIndexIfExists('employee_profile', 'employee_profile_job_function_idx');
        $this->dropIndexIfExists('employee_profile', 'employee_profile_job_title_idx');

        $this->dropColumnIfExists('employee_profile', 'department_id');
        $this->dropColumnIfExists('employee_profile', 'job_function_id');
        $this->dropColumnIfExists('employee_profile', 'job_title_id');
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
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE SET NULL',
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

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP INDEX %s', $tableName, $indexName));
    }

    private function dropForeignKeyIfExists(string $tableName, string $constraintName): void
    {
        if (!$this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableName, $constraintName));
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
