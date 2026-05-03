<?php
// ALEMAC // 2026/05/02 12:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice_type column to invoice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE invoice ADD invoice_type VARCHAR(32) NOT NULL DEFAULT 'invoice'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP invoice_type');
    }
}
