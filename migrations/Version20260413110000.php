<?php
// ALEMAC // 2026/04/13 11:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move device_type from device to device_configs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE device_configs
                ADD device_type VARCHAR(50) NOT NULL DEFAULT 'DEVICE' AFTER people_id"
        );
        $this->addSql(
            "UPDATE device_configs dc
                INNER JOIN device d ON d.id = dc.device_id
            SET dc.device_type = COALESCE(NULLIF(TRIM(d.device_type), ''), dc.device_type)"
        );
        $this->addSql(
            'ALTER TABLE device_configs DROP INDEX device_id, ADD UNIQUE KEY device_company_type (device_id, people_id, device_type)'
        );
        $this->addSql('ALTER TABLE device DROP device_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE device ADD device_type VARCHAR(50) DEFAULT NULL AFTER device'
        );
        $this->addSql(
            "UPDATE device d
                LEFT JOIN (
                    SELECT device_id, MIN(id) AS config_id
                    FROM device_configs
                    GROUP BY device_id
                ) selected_config ON selected_config.device_id = d.id
                LEFT JOIN device_configs dc ON dc.id = selected_config.config_id
            SET d.device_type = dc.device_type"
        );
        $this->addSql(
            'ALTER TABLE device_configs DROP INDEX device_company_type, DROP COLUMN device_type, ADD UNIQUE KEY device_id (device_id, people_id)'
        );
    }
}
