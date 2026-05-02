<?php
// ALEMAC // 2026/05/02 11:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable description column to invoice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP description');
    }
}
