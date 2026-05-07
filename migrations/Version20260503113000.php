<?php
// ALEMAC // 2026/05/03 11:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable timezone_id column to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD timezone_id SMALLINT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP timezone_id');
    }
}
