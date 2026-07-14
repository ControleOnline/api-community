<?php
// ALEMAC // 2026/07/14 12:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714123000 extends AbstractMigration
{
    private const APP_TYPE = 'MANAGER';
    private const MENU_KEY = 'employees_rh';
    private const MENU_LABEL = 'Funcionarios';
    private const CATEGORY_NAME = 'RH';
    private const MODULE_NAME = 'ui-employee';
    private const ROUTE_NAME = 'EmployeesPage';
    private const DETAILS_ROUTE_NAME = 'EmployeeDetailsPage';
    private const MENU_COLOR = '#7C3AED';
    private const MENU_ICON = 'user-check';
    private const CATEGORY_ICON = 'users';
    private const ADMIN_LINK_TYPES = ['owner', 'director', 'manager'];

    public function getDescription(): string
    {
        return 'Add RH manager menu entries for the employee area';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('menu') ||
            !$this->tableExists('routes') ||
            !$this->tableExists('module') ||
            !$this->tableExists('category')
        ) {
            return;
        }

        $this->ensureModule();
        $this->ensureRoute(self::ROUTE_NAME, self::MODULE_NAME, self::MENU_COLOR, self::MENU_ICON);
        $this->ensureRoute(self::DETAILS_ROUTE_NAME, self::MODULE_NAME, self::MENU_COLOR, 'id-card');
        $this->ensureCategory();
        $this->upsertMenu();
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('menu')) {
            $this->addSql(
                "DELETE menu_link_type
                 FROM menu_link_type
                 INNER JOIN menu ON menu.id = menu_link_type.menu_id
                 WHERE menu.app_type = :appType
                 AND menu.menu_type = 'home'
                 AND menu.menu_key = :menuKey",
                [
                    'appType' => self::APP_TYPE,
                    'menuKey' => self::MENU_KEY,
                ]
            );

            $this->addSql(
                "DELETE FROM menu
                 WHERE app_type = :appType
                 AND menu_type = 'home'
                 AND menu_key = :menuKey",
                [
                    'appType' => self::APP_TYPE,
                    'menuKey' => self::MENU_KEY,
                ]
            );
        }

        if ($this->tableExists('routes')) {
            $this->deleteRouteIfUnused(self::ROUTE_NAME);
            $this->deleteRouteIfUnused(self::DETAILS_ROUTE_NAME);
        }

        if ($this->tableExists('module')) {
            $this->addSql(
                "DELETE module
                 FROM module
                 LEFT JOIN routes ON routes.module_id = module.id
                 WHERE module.name = :moduleName
                 AND routes.id IS NULL",
                [
                    'moduleName' => self::MODULE_NAME,
                ]
            );
        }

        if ($this->tableExists('category')) {
            $this->addSql(
                "DELETE category
                 FROM category
                 LEFT JOIN menu ON menu.category_id = category.id
                 WHERE category.context = 'menu'
                 AND category.name = :categoryName
                 AND menu.id IS NULL",
                [
                    'categoryName' => self::CATEGORY_NAME,
                ]
            );
        }
    }

    private function ensureModule(): void
    {
        $this->addSql(
            'UPDATE module
             SET color = :color,
                 icon = :icon,
                 description = :description
             WHERE name = :moduleName',
            [
                'moduleName' => self::MODULE_NAME,
                'color' => self::MENU_COLOR,
                'icon' => self::MENU_ICON,
                'description' => self::MODULE_NAME,
            ]
        );

        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             SELECT :moduleName, :color, :icon, :description
             WHERE NOT EXISTS (SELECT 1 FROM module WHERE name = :moduleName)',
            [
                'moduleName' => self::MODULE_NAME,
                'color' => self::MENU_COLOR,
                'icon' => self::MENU_ICON,
                'description' => self::MODULE_NAME,
            ]
        );
    }

    private function ensureRoute(string $routeName, string $moduleName, string $color, string $icon): void
    {
        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT module.id, :routeName, :color, :icon
             FROM module
             WHERE module.name = :moduleName
             ON DUPLICATE KEY UPDATE
                module_id = VALUES(module_id),
                color = VALUES(color),
                icon = VALUES(icon)',
            [
                'routeName' => $routeName,
                'moduleName' => $moduleName,
                'color' => $color,
                'icon' => $icon,
            ]
        );
    }

    private function ensureCategory(): void
    {
        $this->addSql(
            "UPDATE category
             SET icon = :icon,
                 color = :color
             WHERE context = 'menu'
             AND name = :categoryName",
            [
                'icon' => self::CATEGORY_ICON,
                'color' => self::MENU_COLOR,
                'categoryName' => self::CATEGORY_NAME,
            ]
        );

        $this->addSql(
            "INSERT INTO category (name, icon, color, context, company_id)
             SELECT :categoryName, :icon, :color, 'menu', people_domain.people_id
             FROM people_domain
             WHERE NOT EXISTS (
                SELECT 1 FROM category
                WHERE category.name = :categoryName
                AND category.context = 'menu'
                AND category.company_id = people_domain.people_id
             )
             ORDER BY people_domain.id ASC
             LIMIT 1",
            [
                'categoryName' => self::CATEGORY_NAME,
                'icon' => self::CATEGORY_ICON,
                'color' => self::MENU_COLOR,
            ]
        );
    }

    private function upsertMenu(): void
    {
        $this->addSql(
            "DELETE menu_link_type
             FROM menu_link_type
             INNER JOIN menu ON menu.id = menu_link_type.menu_id
             WHERE menu.app_type = :appType
             AND menu.menu_type = 'home'
             AND menu.menu_key = :menuKey",
            [
                'appType' => self::APP_TYPE,
                'menuKey' => self::MENU_KEY,
            ]
        );

        $this->addSql(
            "INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
             SELECT category.id, :menuLabel, routes.id, :menuKey, :appType, 'home', NULL, 10, 1
             FROM category
             INNER JOIN routes ON routes.route = :routeName
             WHERE category.name = :categoryName
             AND category.context = 'menu'
             ORDER BY category.id ASC
             LIMIT 1
             ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                menu = VALUES(menu),
                route_id = VALUES(route_id),
                menu_type = VALUES(menu_type),
                route_params = VALUES(route_params),
                sort_order = VALUES(sort_order),
                enabled = VALUES(enabled)",
            [
                'menuLabel' => self::MENU_LABEL,
                'menuKey' => self::MENU_KEY,
                'appType' => self::APP_TYPE,
                'routeName' => self::ROUTE_NAME,
                'categoryName' => self::CATEGORY_NAME,
            ]
        );

        foreach (self::ADMIN_LINK_TYPES as $linkType) {
            $this->addSql(
                'INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT menu.id, :linkType
                 FROM menu
                 WHERE menu.app_type = :appType
                 AND menu.menu_type = \'home\'
                 AND menu.menu_key = :menuKey
                 ON DUPLICATE KEY UPDATE link_type = VALUES(link_type)',
                [
                    'linkType' => $linkType,
                    'appType' => self::APP_TYPE,
                    'menuKey' => self::MENU_KEY,
                ]
            );
        }
    }

    private function deleteRouteIfUnused(string $routeName): void
    {
        $this->addSql(
            'DELETE routes
             FROM routes
             LEFT JOIN menu ON menu.route_id = routes.id
             WHERE menu.id IS NULL
             AND routes.route = :routeName',
            [
                'routeName' => $routeName,
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
