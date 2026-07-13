<?php
// ALEMAC // 2026/07/13 13:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713130000 extends AbstractMigration
{
    private const APP_TYPE = 'MANAGER';
    private const MENU_LABEL = 'Minhas empresas';
    private const MODULE = 'ui-manager';
    private const ROUTE = 'MyCompaniesPage';
    private const COLOR = '#0EA5E9';
    private const ICON = 'image';

    public function getDescription(): string
    {
        return 'Point MANAGER menu "Minhas empresas" to the new blank page';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('menu') ||
            !$this->tableExists('routes') ||
            !$this->tableExists('module')
        ) {
            return;
        }

        $this->ensureRoute();

        $this->addSql(
            'UPDATE menu
             INNER JOIN routes ON routes.route = :route
             SET menu.route_id = routes.id
             WHERE menu.app_type = :appType
             AND menu.menu = :menuLabel',
            [
                'route' => self::ROUTE,
                'appType' => self::APP_TYPE,
                'menuLabel' => self::MENU_LABEL,
            ]
        );
    }

    public function down(Schema $schema): void
    {
    }

    private function ensureRoute(): void
    {
        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             SELECT :module, :color, :icon, :description
             WHERE NOT EXISTS (SELECT 1 FROM module WHERE name = :module)',
            [
                'module' => self::MODULE,
                'color' => self::COLOR,
                'icon' => self::ICON,
                'description' => self::MODULE,
            ]
        );

        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT module.id, :route, :color, :icon
             FROM module
             WHERE module.name = :module
             ON DUPLICATE KEY UPDATE
                module_id = VALUES(module_id),
                color = VALUES(color),
                icon = VALUES(icon)',
            [
                'module' => self::MODULE,
                'route' => self::ROUTE,
                'color' => self::COLOR,
                'icon' => self::ICON,
            ]
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
