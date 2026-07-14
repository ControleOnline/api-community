<?php
// ALEMAC // 2026/07/14 13:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714133000 extends AbstractMigration
{
    private const APP_TYPE = 'MANAGER';
    private const CATEGORY_NAME = 'RH';
    private const CATEGORY_ICON = 'users';
    private const MENU_COLOR = '#7C3AED';
    private const MODULE_NAME = 'ui-employee';
    private const ROUTE_NAME = 'RhAttendancePage';
    private const MENU_KEY = 'rh_attendance';
    private const MENU_LABEL = 'Ponto por setor';
    private const ADMIN_LINK_TYPES = ['owner', 'director', 'manager'];

    public function getDescription(): string
    {
        return 'Add RH attendance menu and the people_absence table';
    }

    public function up(Schema $schema): void
    {
        $this->ensurePeopleAbsenceTable();

        if (
            !$this->tableExists('menu') ||
            !$this->tableExists('routes') ||
            !$this->tableExists('module') ||
            !$this->tableExists('category')
        ) {
            return;
        }

        $this->ensureModule();
        $this->ensureRoute();
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

        if ($this->tableExists('people_absence')) {
            $this->addSql('DROP TABLE people_absence');
        }
    }

    private function ensurePeopleAbsenceTable(): void
    {
        if ($this->tableExists('people_absence')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE people_absence (
  id INT AUTO_INCREMENT NOT NULL,
  context VARCHAR(120) NOT NULL,
  company_id INT NOT NULL,
  people_id INT NOT NULL,
  absence_date DATE NOT NULL,
  reason LONGTEXT DEFAULT NULL,
  justification_file_id INT DEFAULT NULL,
  payload JSON DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alter_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX people_absence_context_idx (context),
  INDEX people_absence_company_idx (company_id),
  INDEX people_absence_people_idx (people_id),
  INDEX people_absence_absence_date_idx (absence_date),
  INDEX people_absence_active_idx (active),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );

        $this->addSql('ALTER TABLE people_absence ADD CONSTRAINT FK_PEOPLE_ABSENCE_COMPANY FOREIGN KEY (company_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_absence ADD CONSTRAINT FK_PEOPLE_ABSENCE_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_absence ADD CONSTRAINT FK_PEOPLE_ABSENCE_FILE FOREIGN KEY (justification_file_id) REFERENCES files (id) ON DELETE SET NULL');
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

    private function ensureRoute(): void
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
                'routeName' => self::ROUTE_NAME,
                'moduleName' => self::MODULE_NAME,
                'color' => self::MENU_COLOR,
                'icon' => 'clock-outline',
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
                'menuLabel' => self::MENU_LABEL,
                'menuKey' => self::MENU_KEY,
                'appType' => self::APP_TYPE,
                'routeName' => self::ROUTE_NAME,
                'categoryName' => self::CATEGORY_NAME,
                'routeParams' => json_encode(['context' => 'employment'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sortOrder' => 45,
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
