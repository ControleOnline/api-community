<?php
// ALEMAC // 2026/06/06 17:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add courier vehicle registry for the standalone delivery onboarding screen';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('delivery_courier_vehicle')) {
            return;
        }

        $this->addSql(
            'CREATE TABLE delivery_courier_vehicle (
                id INT AUTO_INCREMENT NOT NULL,
                courier_id INT NOT NULL,
                vehicle_type VARCHAR(20) NOT NULL,
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
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('delivery_courier_vehicle')) {
            $this->addSql('DROP TABLE delivery_courier_vehicle');
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
