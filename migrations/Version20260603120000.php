<?php
// ALEMAC // 2026/06/03 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_file pivot for order attachments';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('order_file')) {
            return;
        }

        $this->addSql('CREATE TABLE order_file (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, file_id INT NOT NULL, INDEX order_file_order_id_idx (order_id), INDEX order_file_file_id_idx (file_id), UNIQUE INDEX order_file_unique (order_id, file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_file ADD CONSTRAINT FK_ORDER_FILE_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_file ADD CONSTRAINT FK_ORDER_FILE_FILE FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('order_file')) {
            return;
        }

        $this->addSql('DROP TABLE order_file');
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
