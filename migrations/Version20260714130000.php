<?php
// ALEMAC // 2026/07/14 13:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714130000 extends AbstractMigration
{
    private const APP_TYPE = 'MANAGER';
    private const CATEGORY_NAME = 'RH';
    private const CATEGORY_ICON = 'users';
    private const MENU_COLOR = '#7C3AED';
    private const MODULE_NAME = 'ui-employee';
    private const ADMIN_LINK_TYPES = ['owner', 'director', 'manager'];

    public function getDescription(): string
    {
        return 'Expand RH manager menu entries with generic lists and dashboard';
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
        $this->ensureCategory();
        $this->ensureRoutes();
        $this->upsertMenus();
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('menu')) {
            foreach ($this->menuSeeds() as $seed) {
                $this->addSql(
                    "DELETE menu_link_type
                     FROM menu_link_type
                     INNER JOIN menu ON menu.id = menu_link_type.menu_id
                     WHERE menu.app_type = :appType
                     AND menu.menu_type = 'home'
                     AND menu.menu_key = :menuKey",
                    [
                        'appType' => self::APP_TYPE,
                        'menuKey' => $seed['menuKey'],
                    ]
                );

                $this->addSql(
                    "DELETE FROM menu
                     WHERE app_type = :appType
                     AND menu_type = 'home'
                     AND menu_key = :menuKey",
                    [
                        'appType' => self::APP_TYPE,
                        'menuKey' => $seed['menuKey'],
                    ]
                );
            }
        }

        if ($this->tableExists('routes')) {
            foreach ($this->routeSeeds() as $routeSeed) {
                $this->deleteRouteIfUnused($routeSeed['route']);
            }
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
                'icon' => 'user-check',
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
                'icon' => 'user-check',
                'description' => self::MODULE_NAME,
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

    private function ensureRoutes(): void
    {
        foreach ($this->routeSeeds() as $routeSeed) {
            $this->ensureRoute(
                $routeSeed['route'],
                $routeSeed['icon'],
                $routeSeed['color'] ?? self::MENU_COLOR
            );
        }
    }

    private function upsertMenus(): void
    {
        foreach ($this->menuSeeds() as $seed) {
            $this->addSql(
                "DELETE menu_link_type
                 FROM menu_link_type
                 INNER JOIN menu ON menu.id = menu_link_type.menu_id
                 WHERE menu.app_type = :appType
                 AND menu.menu_type = 'home'
                 AND menu.menu_key = :menuKey",
                [
                    'appType' => self::APP_TYPE,
                    'menuKey' => $seed['menuKey'],
                ]
            );

            $this->addSql(
                "INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
                 SELECT category.id, :menuLabel, routes.id, :menuKey, :appType, 'home', :routeParams, :sortOrder, 1
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
                    'menuLabel' => $seed['label'],
                    'menuKey' => $seed['menuKey'],
                    'appType' => self::APP_TYPE,
                    'routeName' => $seed['route'],
                    'categoryName' => self::CATEGORY_NAME,
                    'routeParams' => $seed['routeParams'] !== null
                        ? json_encode($seed['routeParams'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : null,
                    'sortOrder' => $seed['sortOrder'],
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
                        'menuKey' => $seed['menuKey'],
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array{route: string, icon: string, color: string}>
     */
    private function routeSeeds(): array
    {
        return [
            ['route' => 'RhHomePage', 'icon' => 'view-dashboard', 'color' => self::MENU_COLOR],
            ['route' => 'RhFunctionsPage', 'icon' => 'badge-account-horizontal', 'color' => self::MENU_COLOR],
            ['route' => 'RhMovementsPage', 'icon' => 'fingerprint', 'color' => self::MENU_COLOR],
            ['route' => 'RhContractsPage', 'icon' => 'file-document-outline', 'color' => self::MENU_COLOR],
            ['route' => 'RhSchedulesPage', 'icon' => 'calendar-clock', 'color' => self::MENU_COLOR],
            ['route' => 'RhExportJobsPage', 'icon' => 'file-export', 'color' => self::MENU_COLOR],
        ];
    }

    /**
     * @return array<int, array{menuKey: string, label: string, route: string, routeParams: array<string, mixed>|null, sortOrder: int}>
     */
    private function menuSeeds(): array
    {
        return [
            [
                'menuKey' => 'rh_home',
                'label' => 'Visao geral',
                'route' => 'RhHomePage',
                'routeParams' => null,
                'sortOrder' => 10,
            ],
            [
                'menuKey' => 'employees_rh',
                'label' => 'Funcionarios',
                'route' => 'EmployeesPage',
                'routeParams' => ['context' => 'employment'],
                'sortOrder' => 20,
            ],
            [
                'menuKey' => 'rh_functions',
                'label' => 'Cargos e funcoes',
                'route' => 'RhFunctionsPage',
                'routeParams' => ['context' => 'employment'],
                'sortOrder' => 30,
            ],
            [
                'menuKey' => 'rh_movements',
                'label' => 'Movimentos',
                'route' => 'RhMovementsPage',
                'routeParams' => ['context' => 'employment'],
                'sortOrder' => 40,
            ],
            [
                'menuKey' => 'rh_contracts',
                'label' => 'Contratos',
                'route' => 'RhContractsPage',
                'routeParams' => ['context' => 'employment'],
                'sortOrder' => 50,
            ],
            [
                'menuKey' => 'rh_schedules',
                'label' => 'Agendas',
                'route' => 'RhSchedulesPage',
                'routeParams' => ['context' => 'employment'],
                'sortOrder' => 60,
            ],
            [
                'menuKey' => 'rh_exports',
                'label' => 'Folha de ponto',
                'route' => 'RhExportJobsPage',
                'routeParams' => [
                    'context' => 'employment',
                    'kind' => 'timesheet',
                ],
                'sortOrder' => 70,
            ],
        ];
    }

    private function ensureRoute(string $routeName, string $icon, string $color): void
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
                'moduleName' => self::MODULE_NAME,
                'color' => $color,
                'icon' => $icon,
            ]
        );
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
