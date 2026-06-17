<?php
// ALEMAC // 2026/06/06 16:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606163000 extends AbstractMigration
{
    private const HUMAN_LINK_TYPES = ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales'];
    private const COURIER_LINK_TYPES = ['courier'];
    private const ADMIN_LINK_TYPES = ['owner', 'director', 'manager'];

    public function getDescription(): string
    {
        return 'Add courier presence schedules, company availability rows and DELIVERY/MANAGER menus';
    }

    public function up(Schema $schema): void
    {
        $this->createTables();
        $this->seedMenus($this->menuSeeds());
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM menu_link_type
             WHERE menu_id IN (
                SELECT id FROM menu WHERE app_type IN ('DELIVERY', 'MANAGER')
                AND menu_key IN ('courier_schedules', 'presence_inbox')
             )"
        );

        $this->addSql("DELETE FROM menu WHERE app_type = 'DELIVERY' AND menu_key = 'courier_schedules'");
        $this->addSql("DELETE FROM menu WHERE app_type = 'MANAGER' AND menu_key = 'presence_inbox'");

        $this->addSql("DELETE FROM routes WHERE route IN ('DeliveryCourierSchedulesPage', 'DeliveryPresenceInboxPage')");

        $this->dropTableIfExists('delivery_courier_company_presence_schedule');
        $this->dropTableIfExists('delivery_courier_company_presence');
        $this->dropTableIfExists('delivery_courier_schedule');
    }

    private function createTables(): void
    {
        if (!$this->tableExists('delivery_courier_schedule')) {
            $this->addSql(
                'CREATE TABLE delivery_courier_schedule (
                    id INT AUTO_INCREMENT NOT NULL,
                    courier_id INT NOT NULL,
                    label VARCHAR(255) NOT NULL,
                    weekday SMALLINT NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    active TINYINT(1) DEFAULT 1 NOT NULL,
                    creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    alter_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                    INDEX delivery_courier_schedule_courier_idx (courier_id),
                    INDEX delivery_courier_schedule_weekday_idx (weekday),
                    INDEX delivery_courier_schedule_active_idx (active),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
            $this->addSql('ALTER TABLE delivery_courier_schedule ADD CONSTRAINT FK_DELIVERY_COURIER_SCHEDULE_COURIER FOREIGN KEY (courier_id) REFERENCES people (id) ON DELETE CASCADE');
        }

        if (!$this->tableExists('delivery_courier_company_presence')) {
            $this->addSql(
                'CREATE TABLE delivery_courier_company_presence (
                    id INT AUTO_INCREMENT NOT NULL,
                    courier_id INT NOT NULL,
                    company_id INT NOT NULL,
                    availability_mode VARCHAR(20) DEFAULT \'automatic\' NOT NULL,
                    is_online TINYINT(1) DEFAULT 0 NOT NULL,
                    manual_reason LONGTEXT DEFAULT NULL,
                    last_online_at DATETIME DEFAULT NULL,
                    last_offline_at DATETIME DEFAULT NULL,
                    creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    alter_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                    UNIQUE INDEX delivery_courier_company_presence_unique (courier_id, company_id),
                    INDEX delivery_courier_company_presence_courier_idx (courier_id),
                    INDEX delivery_courier_company_presence_company_idx (company_id),
                    INDEX delivery_courier_company_presence_mode_idx (availability_mode),
                    INDEX delivery_courier_company_presence_online_idx (is_online),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
            $this->addSql('ALTER TABLE delivery_courier_company_presence ADD CONSTRAINT FK_DELIVERY_COURIER_PRESENCE_COURIER FOREIGN KEY (courier_id) REFERENCES people (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE delivery_courier_company_presence ADD CONSTRAINT FK_DELIVERY_COURIER_PRESENCE_COMPANY FOREIGN KEY (company_id) REFERENCES people (id) ON DELETE CASCADE');
        }

        if (!$this->tableExists('delivery_courier_company_presence_schedule')) {
            $this->addSql(
                'CREATE TABLE delivery_courier_company_presence_schedule (
                    id INT AUTO_INCREMENT NOT NULL,
                    presence_id INT NOT NULL,
                    schedule_id INT NOT NULL,
                    active TINYINT(1) DEFAULT 1 NOT NULL,
                    creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    alter_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                    UNIQUE INDEX delivery_courier_company_presence_schedule_unique (presence_id, schedule_id),
                    INDEX delivery_courier_company_presence_schedule_presence_idx (presence_id),
                    INDEX delivery_courier_company_presence_schedule_schedule_idx (schedule_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
            $this->addSql('ALTER TABLE delivery_courier_company_presence_schedule ADD CONSTRAINT FK_DELIVERY_COURIER_PRESENCE_SCHEDULE_PRESENCE FOREIGN KEY (presence_id) REFERENCES delivery_courier_company_presence (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE delivery_courier_company_presence_schedule ADD CONSTRAINT FK_DELIVERY_COURIER_PRESENCE_SCHEDULE_DEFINITION FOREIGN KEY (schedule_id) REFERENCES delivery_courier_schedule (id) ON DELETE CASCADE');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function menuSeeds(): array
    {
        return [
            $this->menu(
                'DELIVERY',
                'courier_schedules',
                'Horarios do motoboy',
                'Operacao',
                'DeliveryCourierSchedulesPage',
                'clock',
                '#0F766E',
                'ui-logistic',
                self::COURIER_LINK_TYPES,
                40
            ),
            $this->menu(
                'MANAGER',
                'presence_inbox',
                'Presenca dos motoboys',
                'Operacoes',
                'DeliveryPresenceInboxPage',
                'activity',
                '#2563EB',
                'ui-manager',
                self::ADMIN_LINK_TYPES,
                195
            ),
        ];
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

        foreach ($seed['linkTypes'] as $linkType) {
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

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function dropTableIfExists(string $tableName): void
    {
        if ($this->tableExists($tableName)) {
            $this->addSql(sprintf('DROP TABLE %s', $tableName));
        }
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
}
