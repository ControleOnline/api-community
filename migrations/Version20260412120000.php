<?php
// ALEMAC // 2026/04/12 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product_people relation table between products and people';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS product_people (
                id INT(10) NOT NULL AUTO_INCREMENT,
                product_id INT(10) NOT NULL,
                people_id INT(10) NOT NULL,
                role ENUM('supplier','manufacturer','distributor') NOT NULL DEFAULT 'supplier',
                cost_price DECIMAL(10,2) DEFAULT NULL,
                lead_time_days INT(11) DEFAULT NULL,
                supplier_sku VARCHAR(100) DEFAULT NULL,
                priority INT(11) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                KEY IDX_PRODUCT_PEOPLE_PRODUCT (product_id),
                KEY IDX_PRODUCT_PEOPLE_PEOPLE (people_id),
                CONSTRAINT FK_PRODUCT_PEOPLE_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT FK_PRODUCT_PEOPLE_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS product_people');
    }
}
