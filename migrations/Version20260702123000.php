<?php
// ALEMAC // 2026/07/02 12:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702123000 extends AbstractMigration
{
    private const TOOLBAR_CATEGORY = 'Navegacao';
    private const HUMAN_LINK_TYPES = ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales', 'courier'];
    private const CRM_LINK_TYPES = ['owner', 'director', 'manager', 'salesman', 'after-sales'];
    private const COURIER_LINK_TYPES = ['courier'];

    public function getDescription(): string
    {
        return 'Seed toolbar menus for the dynamic bottom navigation';
    }

    public function up(Schema $schema): void
    {
        $this->ensureRoute('HomePage', 'ui-manager', '#0EA5E9', 'home');
        $this->ensureRoute('ProfilePage', 'ui-manager', '#64748B', 'user');
        $this->ensureCategory(self::TOOLBAR_CATEGORY);

        foreach ($this->toolbarMenuSeeds() as $seed) {
            $this->seedMenu($seed);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE menu_link_type
             FROM menu_link_type
             INNER JOIN menu ON menu.id = menu_link_type.menu_id
             WHERE menu.menu_type = 'toolbar'
             AND menu.app_type IN ('MANAGER', 'CRM', 'POS', 'DELIVERY', 'PPC')
             AND menu.menu_key IN (
                'home',
                'crm',
                'opportunities',
                'clients',
                'profile',
                'orders',
                'cash_register',
                'receivables',
                'companies',
                'rate_tables',
                'displays',
                'settings'
             )"
        );

        $this->addSql(
            "DELETE FROM menu
             WHERE menu_type = 'toolbar'
             AND app_type IN ('MANAGER', 'CRM', 'POS', 'DELIVERY', 'PPC')
             AND menu_key IN (
                'home',
                'crm',
                'opportunities',
                'clients',
                'profile',
                'orders',
                'cash_register',
                'receivables',
                'companies',
                'rate_tables',
                'displays',
                'settings'
             )"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function toolbarMenuSeeds(): array
    {
        return [
            $this->menu(
                'MANAGER',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                10
            ),
            $this->menu(
                'MANAGER',
                'opportunities',
                'Oportunidades',
                'Navegacao',
                'CrmIndex',
                'dollar-sign',
                '#F59E0B',
                'ui-crm',
                self::HUMAN_LINK_TYPES,
                20
            ),
            $this->menu(
                'MANAGER',
                'clients',
                'Clientes',
                'Navegacao',
                'ClientsIndex',
                'users',
                '#16A34A',
                'ui-customers',
                self::HUMAN_LINK_TYPES,
                30
            ),
            $this->menu(
                'MANAGER',
                'profile',
                'Perfil',
                'Navegacao',
                'ProfilePage',
                'user',
                '#64748B',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                40
            ),

            $this->menu(
                'CRM',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-manager',
                self::CRM_LINK_TYPES,
                10
            ),
            $this->menu(
                'CRM',
                'crm',
                'CRM',
                'Navegacao',
                'CrmIndex',
                'target',
                '#F59E0B',
                'ui-crm',
                self::CRM_LINK_TYPES,
                20
            ),
            $this->menu(
                'CRM',
                'clients',
                'Clientes',
                'Navegacao',
                'ClientsIndex',
                'users',
                '#16A34A',
                'ui-customers',
                self::CRM_LINK_TYPES,
                30
            ),
            $this->menu(
                'CRM',
                'profile',
                'Profile',
                'Navegacao',
                'ProfilePage',
                'user',
                '#64748B',
                'ui-manager',
                self::CRM_LINK_TYPES,
                40
            ),

            $this->menu(
                'POS',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                10
            ),
            $this->menu(
                'POS',
                'orders',
                'Pedidos',
                'Navegacao',
                'OrderHistoryPage',
                'shopping-bag',
                '#0EA5E9',
                'ui-orders',
                self::HUMAN_LINK_TYPES,
                20
            ),
            $this->menu(
                'POS',
                'cash_register',
                'Caixa',
                'Navegacao',
                'CashRegisterIndex',
                'credit-card',
                '#4682B4',
                'ui-orders',
                self::HUMAN_LINK_TYPES,
                30
            ),
            $this->menu(
                'POS',
                'profile',
                'Perfil',
                'Navegacao',
                'ProfilePage',
                'user',
                '#64748B',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                40
            ),

            $this->menu(
                'SHOP',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-shop',
                self::HUMAN_LINK_TYPES,
                10
            ),
            $this->menu(
                'SHOP',
                'orders',
                'Pedidos',
                'Navegacao',
                'ShopIndex',
                'shopping-bag',
                '#0EA5E9',
                'ui-shop',
                self::HUMAN_LINK_TYPES,
                20
            ),
            $this->menu(
                'SHOP',
                'profile',
                'Perfil',
                'Navegacao',
                'ProfilePage',
                'user',
                '#64748B',
                'ui-shop',
                self::HUMAN_LINK_TYPES,
                30
            ),
            $this->menu(
                'SHOP',
                'settings',
                'Configurações',
                'Navegacao',
                'SettingsPage',
                'settings',
                '#64748B',
                'ui-shop',
                self::HUMAN_LINK_TYPES,
                40
            ),

            $this->menu(
                'DELIVERY',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-manager',
                self::COURIER_LINK_TYPES,
                10
            ),
            $this->menu(
                'DELIVERY',
                'orders',
                'Pedidos',
                'Navegacao',
                'DeliveryOrdersPage',
                'shopping-bag',
                '#0EA5E9',
                'ui-logistic',
                self::COURIER_LINK_TYPES,
                20
            ),
            $this->menu(
                'DELIVERY',
                'receivables',
                'Recebiveis',
                'Navegacao',
                'DeliveryReceivablesPage',
                'dollar-sign',
                '#16A34A',
                'ui-logistic',
                self::COURIER_LINK_TYPES,
                30
            ),
            $this->menu(
                'DELIVERY',
                'companies',
                'Empresas',
                'Navegacao',
                'DeliveryCompaniesPage',
                'briefcase',
                '#7C3AED',
                'ui-logistic',
                self::COURIER_LINK_TYPES,
                40
            ),
            $this->menu(
                'DELIVERY',
                'rate_tables',
                'Tabelas',
                'Navegacao',
                'DeliveryRateTablesPage',
                'list',
                '#64748B',
                'ui-logistic',
                self::COURIER_LINK_TYPES,
                50
            ),

            $this->menu(
                'PPC',
                'home',
                'Home',
                'Navegacao',
                'HomePage',
                'home',
                '#0EA5E9',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                10
            ),
            $this->menu(
                'PPC',
                'displays',
                'Displays',
                'Navegacao',
                'DisplayList',
                'monitor',
                '#7C3AED',
                'ui-ppc',
                self::HUMAN_LINK_TYPES,
                20
            ),
            $this->menu(
                'PPC',
                'profile',
                'Profile',
                'Navegacao',
                'ProfilePage',
                'user',
                '#64748B',
                'ui-manager',
                self::HUMAN_LINK_TYPES,
                30
            ),
        ];
    }

    /**
     * @param array<int, string> $linkTypes
     * @return array<string, mixed>
     */
    private function menu(
        string $appType,
        string $menuKey,
        string $label,
        string $category,
        string $route,
        string $icon,
        string $color,
        string $module,
        array $linkTypes,
        int $sortOrder
    ): array {
        return compact(
            'appType',
            'menuKey',
            'label',
            'category',
            'route',
            'icon',
            'color',
            'module',
            'linkTypes',
            'sortOrder'
        );
    }

    /**
     * @param array<string, mixed> $seed
     */
    private function seedMenu(array $seed): void
    {
        $this->ensureModule($seed['module'], $seed['color'], $seed['icon'], $seed['module']);
        $this->ensureCategory((string) $seed['category']);

        $this->addSql(
            "INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
             SELECT category.id, :label, routes.id, :menuKey, :appType, 'toolbar', NULL, :sortOrder, 1
             FROM category
             INNER JOIN routes ON routes.route = :route
             WHERE category.name = :category
             AND category.context = 'menu'
             ORDER BY category.id ASC
             LIMIT 1
             ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                menu = VALUES(menu),
                route_id = VALUES(route_id),
                route_params = VALUES(route_params),
                sort_order = VALUES(sort_order),
                enabled = VALUES(enabled)",
            [
                'label' => $seed['label'],
                'menuKey' => $seed['menuKey'],
                'appType' => $seed['appType'],
                'sortOrder' => $seed['sortOrder'],
                'route' => $seed['route'],
                'category' => $seed['category'],
            ]
        );

        $this->addSql(
            "DELETE menu_link_type
             FROM menu_link_type
             INNER JOIN menu ON menu.id = menu_link_type.menu_id
             WHERE menu.app_type = :appType
             AND menu.menu_type = 'toolbar'
             AND menu.menu_key = :menuKey",
            [
                'appType' => $seed['appType'],
                'menuKey' => $seed['menuKey'],
            ]
        );

        foreach ($seed['linkTypes'] as $linkType) {
            $this->addSql(
                'INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT menu.id, :linkType
                 FROM menu
                 WHERE menu.app_type = :appType
                 AND menu.menu_type = \'toolbar\'
                 AND menu.menu_key = :menuKey
                 ON DUPLICATE KEY UPDATE link_type = VALUES(link_type)',
                [
                    'linkType' => $linkType,
                    'appType' => $seed['appType'],
                    'menuKey' => $seed['menuKey'],
                ]
            );
        }
    }

    private function ensureModule(string $name, string $color, string $icon, string $description): void
    {
        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             SELECT :name, :color, :icon, :description
             WHERE NOT EXISTS (SELECT 1 FROM module WHERE name = :name)',
            [
                'name' => $name,
                'color' => $color,
                'icon' => $icon,
                'description' => $description,
            ]
        );
    }

    private function ensureRoute(string $route, string $module, string $color, string $icon): void
    {
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
                'route' => $route,
                'module' => $module,
                'color' => $color,
                'icon' => $icon,
            ]
        );
    }

    private function ensureCategory(string $name): void
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
                'name' => self::TOOLBAR_CATEGORY,
                'icon' => 'menu',
                'color' => '#64748B',
            ]
        );
    }
}
