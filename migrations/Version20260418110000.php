<?php
// ALEMAC // 2026/04/18 11:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type column to log table for entity logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE log
                ADD type VARCHAR(50) NOT NULL DEFAULT 'entity' AFTER user_id"
        );
        $this->addSql(
            "UPDATE log
            SET type = 'entity'
            WHERE type IS NULL OR TRIM(type) = ''"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log DROP type');
    }
}
