<?php
// ALEMAC // 2026/07/11 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711120000 extends AbstractMigration
{
    private const APP_TYPE = 'ADMIN';
    private const MENU_TYPE = 'home';
    private const MENU_KEY = 'test_results';
    private const MENU_LABEL = 'Resultados de testes';
    private const CATEGORY = 'Configuracoes';
    private const MODULE = 'ui-tests';
    private const ROUTE = 'TestsPlaygroundPage';
    private const COLOR = '#0EA5E9';
    private const ICON = 'clipboard';
    private const SORT_ORDER = 525;
    private const CATEGORY_COLOR = '#64748B';
    private const CATEGORY_ICON = 'settings';

    public function getDescription(): string
    {
        return 'Add the tests playground menu entry to ADMIN';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('menu') ||
            !$this->tableExists('routes') ||
            !$this->tableExists('module') ||
            !$this->tableExists('category') ||
            !$this->tableExists('people_domain')
        ) {
            return;
        }

        $this->ensureRoute();
        $this->ensureCategory();
        $this->clearMenuLinkTypes();
        $this->seedMenu();
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        $this->clearMenuLinkTypes();

        $this->addSql(
            'DELETE FROM menu
             WHERE app_type = :appType
             AND menu_type = :menuType
             AND menu_key = :menuKey',
            [
                'appType' => self::APP_TYPE,
                'menuType' => self::MENU_TYPE,
                'menuKey' => self::MENU_KEY,
            ]
        );
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

    private function ensureCategory(): void
    {
        $this->addSql(
            "INSERT INTO category (name, icon, color, context, company_id)
             SELECT :name, :icon, :color, 'menu', people_domain.people_id
             FROM people_domain
             WHERE NOT EXISTS (
                SELECT 1 FROM category
                WHERE category.name = :name
                AND category.context = 'menu'
                AND category.company_id = people_domain.people_id
             )
             ORDER BY people_domain.id ASC
             LIMIT 1",
            [
                'name' => self::CATEGORY,
                'icon' => self::CATEGORY_ICON,
                'color' => self::CATEGORY_COLOR,
            ]
        );
    }

    private function clearMenuLinkTypes(): void
    {
        $this->addSql(
            'DELETE menu_link_type
             FROM menu_link_type
             INNER JOIN menu ON menu.id = menu_link_type.menu_id
             WHERE menu.app_type = :appType
             AND menu.menu_type = :menuType
             AND menu.menu_key = :menuKey',
            [
                'appType' => self::APP_TYPE,
                'menuType' => self::MENU_TYPE,
                'menuKey' => self::MENU_KEY,
            ]
        );
    }

    private function seedMenu(): void
    {
        $this->addSql(
            'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
             SELECT category.id, :label, routes.id, :menuKey, :appType, :menuType, NULL, :sortOrder, 1
             FROM category
             INNER JOIN routes ON routes.route = :route
             WHERE category.name = :category
             AND category.context = \'menu\'
             ORDER BY category.id ASC
             LIMIT 1
             ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                menu = VALUES(menu),
                route_id = VALUES(route_id),
                menu_type = VALUES(menu_type),
                route_params = VALUES(route_params),
                sort_order = VALUES(sort_order),
                enabled = VALUES(enabled)',
            [
                'label' => self::MENU_LABEL,
                'menuKey' => self::MENU_KEY,
                'appType' => self::APP_TYPE,
                'menuType' => self::MENU_TYPE,
                'sortOrder' => self::SORT_ORDER,
                'route' => self::ROUTE,
                'category' => self::CATEGORY,
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
