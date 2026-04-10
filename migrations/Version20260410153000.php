<?php
// ALEMAC // 2026/04/10 15:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add device_type and metadata fields to device table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE device ADD device_type VARCHAR(50) DEFAULT NULL, ADD metadata JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE device DROP device_type, DROP metadata');
    }
}
