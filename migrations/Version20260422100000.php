<?php
// ALEMAC // 2026/04/22 10:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow generic logs without entity reference in log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE log
                CHANGE `row` `row` INT DEFAULT NULL,
                CHANGE `class` `class` VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE log
                SET `class` = 'ControleOnline\\\\Entity\\\\Log', `row` = id
              WHERE `class` IS NULL OR `row` IS NULL"
        );

        $this->addSql(
            'ALTER TABLE log
                CHANGE `row` `row` INT NOT NULL,
                CHANGE `class` `class` VARCHAR(255) NOT NULL'
        );
    }
}
