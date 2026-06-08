<?php

// ALEMAC // 2026/06/07 19:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rich identity fields to courier vehicles';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_courier_vehicle')) {
            $this->addSql(
                'CREATE TABLE delivery_courier_vehicle (
                    id INT AUTO_INCREMENT NOT NULL,
                    courier_id INT NOT NULL,
                    vehicle_type VARCHAR(20) NOT NULL,
                    brand VARCHAR(80) DEFAULT NULL,
                    model VARCHAR(120) DEFAULT NULL,
                    plate VARCHAR(20) DEFAULT NULL,
                    year INT DEFAULT NULL,
                    color VARCHAR(60) DEFAULT NULL,
                    creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    alter_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                    UNIQUE INDEX delivery_courier_vehicle_unique (courier_id),
                    INDEX delivery_courier_vehicle_courier_idx (courier_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );

            $this->addSql(
                'ALTER TABLE delivery_courier_vehicle
                 ADD CONSTRAINT FK_DELIVERY_COURIER_VEHICLE_COURIER
                 FOREIGN KEY (courier_id) REFERENCES people (id)
                 ON DELETE CASCADE'
            );

            return;
        }

        $this->addColumnIfMissing('delivery_courier_vehicle', 'brand', 'VARCHAR(80) DEFAULT NULL');
        $this->addColumnIfMissing('delivery_courier_vehicle', 'model', 'VARCHAR(120) DEFAULT NULL');
        $this->addColumnIfMissing('delivery_courier_vehicle', 'plate', 'VARCHAR(20) DEFAULT NULL');
        $this->addColumnIfMissing('delivery_courier_vehicle', 'year', 'INT DEFAULT NULL');
        $this->addColumnIfMissing('delivery_courier_vehicle', 'color', 'VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('delivery_courier_vehicle')) {
            return;
        }

        foreach (['color', 'year', 'plate', 'model', 'brand'] as $column) {
            if ($this->columnExists('delivery_courier_vehicle', $column)) {
                $this->addSql(sprintf('ALTER TABLE delivery_courier_vehicle DROP COLUMN %s', $column));
            }
        }
    }

    private function addColumnIfMissing(string $tableName, string $columnName, string $definition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $tableName, $columnName, $definition));
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
}
