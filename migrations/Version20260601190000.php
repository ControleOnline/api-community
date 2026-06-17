<?php
// ALEMAC // 2026/06/01 19:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601190000 extends AbstractMigration
{
    private const HUMAN_LINK_TYPES = ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales'];
    private const COURIER_LINK_TYPES = ['courier'];

    public function getDescription(): string
    {
        return 'Seed DELIVERY menus for orders, receivables and homologated companies';
    }

    public function up(Schema $schema): void
    {
        $this->seedMenus($this->deliveryMenuSeeds());
        $this->addSql(
            "DELETE FROM menu_link_type
             WHERE menu_id IN (
                SELECT id FROM menu WHERE app_type = 'DELIVERY'
             )"
        );
        $this->seedMenuLinkTypes($this->deliveryMenuSeeds(), self::COURIER_LINK_TYPES);
        $this->addSql("UPDATE menu SET enabled = 0 WHERE app_type = 'DELIVERY' AND menu_key = 'prints'");
    }

    public function down(Schema $schema): void
    {
        $this->seedMenus($this->legacyDeliveryMenuSeeds());
        $this->addSql(
            "DELETE FROM menu_link_type
             WHERE menu_id IN (
                SELECT id FROM menu WHERE app_type = 'DELIVERY'
             )"
        );
        $this->seedMenuLinkTypes($this->legacyDeliveryMenuSeeds(), self::HUMAN_LINK_TYPES);
        $this->addSql("DELETE FROM menu WHERE app_type = 'DELIVERY' AND menu_key IN ('receivables', 'companies')");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function deliveryMenuSeeds(): array
    {
        return [
            $this->menu(
                'DELIVERY',
                'orders',
                'Pedidos',
                'Operacao',
                'DeliveryOrdersPage',
                'shopping-bag',
                '#0EA5E9',
                'ui-logistic',
                10
            ),
            $this->menu(
                'DELIVERY',
                'receivables',
                'Recebiveis',
                'Operacao',
                'DeliveryReceivablesPage',
                'dollar-sign',
                '#16A34A',
                'ui-logistic',
                20
            ),
            $this->menu(
                'DELIVERY',
                'companies',
                'Empresas homologadas',
                'Operacao',
                'DeliveryCompaniesPage',
                'briefcase',
                '#7C3AED',
                'ui-logistic',
                30
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function legacyDeliveryMenuSeeds(): array
    {
        return [
            $this->menu(
                'DELIVERY',
                'orders',
                'Pedidos',
                'Operacao',
                'OrderHistoryPage',
                'shopping-bag',
                '#0EA5E9',
                'ui-orders',
                10
            ),
            $this->menu(
                'DELIVERY',
                'prints',
                'Impressoes',
                'Operacao',
                'PrintQueuePage',
                'printer',
                '#0F766E',
                'ui-orders',
                20
            ),
        ];
    }

    /**
     * @param array<string, mixed> $seed
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
            'sortOrder'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $seeds
     */
    private function seedMenus(array $seeds): void
    {
        foreach ($seeds as $seed) {
            $this->seedMenu($seed);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $seeds
     * @param array<int, string> $linkTypes
     */
    private function seedMenuLinkTypes(array $seeds, array $linkTypes): void
    {
        foreach ($seeds as $seed) {
            foreach ($linkTypes as $linkType) {
                $this->addSql(
                    'INSERT INTO menu_link_type (menu_id, link_type)
                     SELECT menu.id, :linkType
                     FROM menu
                     WHERE menu.app_type = :appType
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
    }

    /**
     * @param array<string, mixed> $seed
     */
    private function seedMenu(array $seed): void
    {
        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             SELECT :module, :color, :icon, :description
             WHERE NOT EXISTS (SELECT 1 FROM module WHERE name = :module)',
            [
                'module' => $seed['module'],
                'color' => $seed['color'],
                'icon' => $seed['icon'],
                'description' => $seed['module'],
            ]
        );

        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT module.id, :route, :color, :icon
             FROM module
             WHERE module.name = :module
             ON DUPLICATE KEY UPDATE module_id = VALUES(module_id), color = VALUES(color), icon = VALUES(icon)',
            [
                'module' => $seed['module'],
                'route' => $seed['route'],
                'color' => $seed['color'],
                'icon' => $seed['icon'],
            ]
        );

        $this->addSql(
            "INSERT INTO category (name, icon, color, context, company_id)
             SELECT :category, :icon, :color, 'menu', people_domain.people_id
             FROM people_domain
             WHERE NOT EXISTS (
                SELECT 1 FROM category
                WHERE category.name = :category
                AND category.context = 'menu'
                AND category.company_id = people_domain.people_id
             )
             ORDER BY people_domain.id ASC
             LIMIT 1",
            [
                'category' => $seed['category'],
                'icon' => $seed['icon'],
                'color' => $seed['color'],
            ]
        );

        $this->addSql(
            "INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, route_params, sort_order, enabled)
             SELECT category.id, :label, routes.id, :menuKey, :appType, :routeParams, :sortOrder, 1
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
                'routeParams' => null,
                'sortOrder' => $seed['sortOrder'],
                'route' => $seed['route'],
                'category' => $seed['category'],
            ]
        );
    }
}
