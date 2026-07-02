<?php
// ALEMAC // 2026/07/02 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add menu_type to menu and split menu uniqueness by context';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        if (!$this->columnExists('menu', 'menu_type')) {
            $this->addSql("ALTER TABLE menu ADD menu_type VARCHAR(30) NOT NULL DEFAULT 'home' AFTER app_type");
        }

        if ($this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('DROP INDEX menu_app_key_unique ON menu');
        }

        if (!$this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('CREATE UNIQUE INDEX menu_app_key_unique ON menu (app_type, menu_type, menu_key)');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        if ($this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('DROP INDEX menu_app_key_unique ON menu');
        }

        if ($this->columnExists('menu', 'menu_type')) {
            $this->addSql('ALTER TABLE menu DROP COLUMN menu_type');
        }

        if (!$this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('CREATE UNIQUE INDEX menu_app_key_unique ON menu (app_type, menu_key)');
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
}
