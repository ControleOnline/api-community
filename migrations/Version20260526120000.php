<?php
// ALEMAC // 2026/05/26 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add externalCode to orders';
    }

    public function up(Schema $schema): void
    {
        if ($this->columnExists('orders', 'external_code')) {
            return;
        }

        $this->addSql('ALTER TABLE `orders` ADD `external_code` VARCHAR(255) DEFAULT NULL AFTER `id`');
    }

    public function down(Schema $schema): void
    {
        if (!$this->columnExists('orders', 'external_code')) {
            return;
        }

        $this->addSql('ALTER TABLE `orders` DROP COLUMN `external_code`');
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
