<?php
// ALEMAC // 2026/02/28 18:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial ERP schema (MariaDB 10.11 baseline)';
    }

    public function up(Schema $schema): void
    {
        $sqlFile = __DIR__ . '/mysql_base_install.sql';

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException('SQL file not found: ' . $sqlFile);
        }

        // Lê o dump
        $sql = file_get_contents($sqlFile);

        // Limpa usando classe externa
        $sql = DumpCleanUp::clean($sql);

        // Executa SQL limpo
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        throw new \Doctrine\Migrations\Exception\IrreversibleMigration(
            'This is the initial baseline migration and cannot be reverted.'
        );
    }
}