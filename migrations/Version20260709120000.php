<?php
// ALEMAC // 2026/07/09 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move menu_access from MANAGER to ADMIN';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        $this->addSql(
            "DELETE admin_menu
             FROM menu admin_menu
             WHERE admin_menu.app_type = 'ADMIN'
             AND admin_menu.menu_key = 'menu_access'
             AND admin_menu.menu_type = 'home'
             AND EXISTS (
                SELECT 1
                FROM menu manager_menu
                WHERE manager_menu.app_type = 'MANAGER'
                AND manager_menu.menu_key = 'menu_access'
                AND manager_menu.menu_type = 'home'
             )"
        );

        $this->addSql(
            "UPDATE menu
             SET app_type = 'ADMIN'
             WHERE app_type = 'MANAGER'
             AND menu_key = 'menu_access'
             AND menu_type = 'home'"
        );
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        $this->addSql(
            "DELETE manager_menu
             FROM menu manager_menu
             WHERE manager_menu.app_type = 'MANAGER'
             AND manager_menu.menu_key = 'menu_access'
             AND manager_menu.menu_type = 'home'
             AND EXISTS (
                SELECT 1
                FROM menu admin_menu
                WHERE admin_menu.app_type = 'ADMIN'
                AND admin_menu.menu_key = 'menu_access'
                AND admin_menu.menu_type = 'home'
             )"
        );

        $this->addSql(
            "UPDATE menu
             SET app_type = 'MANAGER'
             WHERE app_type = 'ADMIN'
             AND menu_key = 'menu_access'
             AND menu_type = 'home'"
        );
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
