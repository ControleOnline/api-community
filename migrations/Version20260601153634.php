<?php
// ALEMAC // 2026/06/01 15:36:34

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601153634 extends AbstractMigration
{
    private const APP_TYPE = 'SERVICE';
    private const MENU_KEY = 'label_creator';
    private const ROUTE = 'LabelsPage';
    private const CATEGORY = 'Operacoes';
    private const LABEL = 'Criador de etiquetas';

    public function getDescription(): string
    {
        return 'Seed SERVICE menu for label creator access';
    }

    public function up(Schema $schema): void
    {
        if (
            !$this->tableExists('menu')
            || !$this->tableExists('routes')
            || !$this->tableExists('category')
        ) {
            return;
        }

        $this->seedServiceMenu();
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        if ($this->tableExists('menu_link_type')) {
            $this->addSql(
                "DELETE menu_link_type FROM menu_link_type
                 INNER JOIN menu ON menu.id = menu_link_type.menu_id
                 WHERE menu.app_type = :appType
                 AND menu.menu_key = :menuKey",
                [
                    'appType' => self::APP_TYPE,
                    'menuKey' => self::MENU_KEY,
                ]
            );
        }

        $this->addSql(
            'DELETE FROM menu WHERE app_type = :appType AND menu_key = :menuKey',
            [
                'appType' => self::APP_TYPE,
                'menuKey' => self::MENU_KEY,
            ]
        );
    }

    private function seedServiceMenu(): void
    {
        $this->addSql(
            "INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, route_params, sort_order, enabled)
             SELECT category.id, :label, routes.id, :menuKey, :appType, NULL, 10, 1
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
                'label' => self::LABEL,
                'menuKey' => self::MENU_KEY,
                'appType' => self::APP_TYPE,
                'route' => self::ROUTE,
                'category' => self::CATEGORY,
            ]
        );

        if (!$this->tableExists('menu_link_type')) {
            return;
        }

        $this->addSql(
            "DELETE menu_link_type FROM menu_link_type
             INNER JOIN menu ON menu.id = menu_link_type.menu_id
             WHERE menu.app_type = :appType
             AND menu.menu_key = :menuKey
             AND menu_link_type.link_type NOT IN ('employee', 'owner')",
            [
                'appType' => self::APP_TYPE,
                'menuKey' => self::MENU_KEY,
            ]
        );

        foreach (['employee', 'owner'] as $linkType) {
            $this->addSql(
                'INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT menu.id, :linkType
                 FROM menu
                 WHERE menu.app_type = :appType
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

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
