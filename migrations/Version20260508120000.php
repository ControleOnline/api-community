<?php
// ALEMAC // 2026/05/08 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add retry counter to integration queue records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE integration ADD retry INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE integration DROP retry');
    }
}
