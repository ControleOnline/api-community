<?php
// ALEMAC // 2026/05/09 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509120000 extends AbstractMigration
{
    private const HUMAN_LINK_TYPES = ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales'];
    private const ADMIN_LINK_TYPES = ['owner', 'director', 'manager'];
    private const CRM_LINK_TYPES = ['owner', 'director', 'manager', 'salesman', 'after-sales'];

    public function getDescription(): string
    {
        return 'Move home menu permissions to people_link.link_type and app_type';
    }

    public function up(Schema $schema): void
    {
        $this->extendMenuTable($schema);
        $this->createMenuLinkTypeTable($schema);
        $this->seedMenus();
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('menu_link_type')) {
            $this->addSql('DROP TABLE menu_link_type');
        }

        if (!$this->tableExists('menu')) {
            return;
        }

        if ($this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('DROP INDEX menu_app_key_unique ON menu');
        }

        if ($this->indexExists('menu', 'menu_app_type_idx')) {
            $this->addSql('DROP INDEX menu_app_type_idx ON menu');
        }

        foreach (['enabled', 'sort_order', 'route_params', 'app_type', 'menu_key'] as $column) {
            if ($this->columnExists('menu', $column)) {
                $this->addSql(sprintf('ALTER TABLE menu DROP COLUMN %s', $column));
            }
        }
    }

    private function extendMenuTable(Schema $schema): void
    {
        if (!$this->tableExists('menu')) {
            return;
        }

        if (!$this->columnExists('menu', 'menu_key')) {
            $this->addSql('ALTER TABLE menu ADD menu_key VARCHAR(100) DEFAULT NULL');
            $this->addSql("UPDATE menu INNER JOIN routes ON routes.id = menu.route_id SET menu.menu_key = CONCAT('legacy_', routes.route, '_', menu.id) WHERE menu.menu_key IS NULL OR menu.menu_key = ''");
            $this->addSql('ALTER TABLE menu MODIFY menu_key VARCHAR(100) NOT NULL');
        }

        if (!$this->columnExists('menu', 'app_type')) {
            $this->addSql("ALTER TABLE menu ADD app_type VARCHAR(30) DEFAULT 'MANAGER' NOT NULL");
        }

        if (!$this->columnExists('menu', 'route_params')) {
            $this->addSql('ALTER TABLE menu ADD route_params LONGTEXT DEFAULT NULL CHECK (json_valid(route_params))');
        }

        if (!$this->columnExists('menu', 'sort_order')) {
            $this->addSql('ALTER TABLE menu ADD sort_order INT DEFAULT 0 NOT NULL');
        }

        if (!$this->columnExists('menu', 'enabled')) {
            $this->addSql('ALTER TABLE menu ADD enabled TINYINT(1) DEFAULT 1 NOT NULL');
        }

        if (!$this->indexExists('menu', 'menu_app_type_idx')) {
            $this->addSql('CREATE INDEX menu_app_type_idx ON menu (app_type)');
        }

        if (!$this->indexExists('menu', 'menu_app_key_unique')) {
            $this->addSql('CREATE UNIQUE INDEX menu_app_key_unique ON menu (app_type, menu_key)');
        }
    }

    private function createMenuLinkTypeTable(Schema $schema): void
    {
        if ($this->tableExists('menu_link_type')) {
            return;
        }

        $this->addSql('CREATE TABLE menu_link_type (id INT AUTO_INCREMENT NOT NULL, menu_id INT NOT NULL, link_type VARCHAR(30) NOT NULL, INDEX menu_link_type_link_type_idx (link_type), INDEX IDX_486AA71ACCD7E912 (menu_id), UNIQUE INDEX menu_link_type_unique (menu_id, link_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE menu_link_type ADD CONSTRAINT FK_486AA71ACCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function seedMenus(): void
    {
        foreach ($this->menuSeeds() as $seed) {
            $this->seedMenu($seed);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function menuSeeds(): array
    {
        $human = self::HUMAN_LINK_TYPES;
        $admin = self::ADMIN_LINK_TYPES;
        $crm = self::CRM_LINK_TYPES;
        $managerBasic = ['employee', 'owner', 'director', 'manager'];

        return [
            $this->menu('MANAGER', 'financial_hub', 'Financeiro', 'Financeiro', 'FinancialHubPage', 'dollar-sign', '#0284C7', 'ui-manager', $admin, 10),
            $this->menu('MANAGER', 'income_statement', 'Resultados', 'Financeiro', 'IncomeStatement', 'bar-chart-2', '#0284C7', 'ui-financial', $admin, 20),
            $this->menu('MANAGER', 'payables', 'Contas a pagar', 'Financeiro', 'Payables', 'arrow-up-circle', '#DC2626', 'ui-financial', $admin, 30),
            $this->menu('MANAGER', 'receivables', 'Contas a receber', 'Financeiro', 'Receivables', 'arrow-down-circle', '#16A34A', 'ui-financial', $admin, 40),
            $this->menu('MANAGER', 'own_transfers', 'Transferencias', 'Financeiro', 'OwnTransfers', 'repeat', '#6366F1', 'ui-financial', $admin, 50),
            $this->menu('MANAGER', 'wallets', 'Carteiras', 'Financeiro', 'WalletsPage', 'credit-card', '#0F766E', 'ui-financial', $admin, 60),
            $this->menu('MANAGER', 'invoice_categories', 'Categorias financeiras', 'Financeiro', 'InvoiceCategoriesPage', 'folder', '#475569', 'ui-financial', $admin, 70),

            $this->menu('MANAGER', 'sales_orders', 'Pedidos de venda', 'Operacoes', 'OrderHistoryPage', 'shopping-bag', '#0EA5E9', 'ui-orders', $managerBasic, 110, ['orderTypeFilter' => 'sale', 'historyTitle' => 'Pedidos de venda']),
            $this->menu('MANAGER', 'providers', 'Fornecedores', 'Operacoes', 'ProvidersIndex', 'briefcase', '#F59E0B', 'ui-customers', $admin, 120),
            $this->menu('MANAGER', 'products', 'Produtos', 'Operacoes', 'CategoriesPage', 'package', '#16A34A', 'ui-products', $admin, 130),
            $this->menu('MANAGER', 'inventory', 'Estoque', 'Operacoes', 'InventoriesPage', 'archive', '#D97706', 'ui-products', $admin, 140),
            $this->menu('MANAGER', 'purchase_orders', 'Compras', 'Operacoes', 'OrderHistoryPage', 'truck', '#EA580C', 'ui-orders', $admin, 150, ['orderTypeFilter' => 'purchase', 'historyTitle' => 'Compras']),
            $this->menu('MANAGER', 'purchase_suggestions', 'Sugestoes de compra', 'Operacoes', 'PurchasingSuggestion', 'truck', '#7C3AED', 'ui-products', $admin, 160),
            $this->menu('MANAGER', 'purchase_form', 'Registrar compra', 'Operacoes', 'PurchaseFormPage', 'shopping-cart', '#16A34A', 'ui-products', $admin, 170),
            $this->menu('MANAGER', 'menu_costs', 'Custos do cardapio', 'Operacoes', 'MenuCostsPage', 'pie-chart', '#EA580C', 'ui-manager', $admin, 180),
            $this->menu('MANAGER', 'supplies', 'Insumos', 'Operacoes', 'CategoriesPage', 'box', '#16A34A', 'ui-products', $admin, 190, ['context' => 'supplies', 'interactionMode' => 'manager']),
            $this->menu('MANAGER', 'labels', 'Etiquetas', 'Operacoes', 'LabelsPage', 'tag', '#0284C7', 'ui-manager', $admin, 200),
            $this->menu('MANAGER', 'display_list', 'Displays', 'Operacoes', 'DisplayList', 'monitor', '#7C3AED', 'ui-ppc', $admin, 210),

            $this->menu('MANAGER', 'clients', 'Clientes', 'Comercial', 'ClientsIndex', 'users', '#16A34A', 'ui-customers', $admin, 310),
            $this->menu('MANAGER', 'employees', 'Colaboradores', 'Comercial', 'EmployeesIndex', 'user-check', '#7C3AED', 'ui-customers', $admin, 320),
            $this->menu('MANAGER', 'pdv', 'PDV', 'Comercial', 'PdvPage', 'shopping-bag', '#EA580C', 'ui-manager', $managerBasic, 330),
            $this->menu('MANAGER', 'linked_order_settlement', 'Acerto de pedidos', 'Comercial', 'LinkedOrderSettlementPage', 'layers', '#0284C7', 'ui-orders', $admin, 340),

            $this->menu('MANAGER', 'model_templates', 'Editor de modelos', 'Modelos', 'ModelTemplatesPage', 'edit-3', '#EA580C', 'ui-manager', $admin, 410),
            $this->menu('MANAGER', 'model_labels', 'Etiquetas', 'Modelos', 'LabelsPage', 'tag', '#0284C7', 'ui-manager', $admin, 420),

            $this->menu('MANAGER', 'configurator', 'Configurador', 'Configuracoes', 'ConfiguratorPage', 'settings', '#64748B', 'ui-manager', $admin, 510),
            $this->menu('MANAGER', 'menu_access', 'Menus por perfil', 'Configuracoes', 'MenuAccessConfigPage', 'list', '#64748B', 'ui-manager', [], 520),
            $this->menu('MANAGER', 'devices', 'Dispositivos', 'Configuracoes', 'DevicesIndex', 'credit-card', '#F59E0B', 'ui-manager', $admin, 530),
            $this->menu('MANAGER', 'connections', 'Conexoes', 'Configuracoes', 'ConnectionsPage', 'link', '#0284C7', 'ui-manager', $admin, 540),
            $this->menu('MANAGER', 'integrations', 'Integracoes', 'Configuracoes', 'IntegrationsPage', 'sliders', '#7C3AED', 'ui-manager', $admin, 550),
            $this->menu('MANAGER', 'manager_categories', 'Categorias', 'Configuracoes', 'ManagerCategoriesPage', 'folder', '#475569', 'ui-manager', $admin, 560),
            $this->menu('MANAGER', 'translations_review', 'Traducoes', 'Configuracoes', 'TranslationsReviewPage', 'globe', '#0F766E', 'ui-manager', $admin, 570),

            $this->menu('CRM', 'opportunities', 'Oportunidades', 'Comercial', 'CrmIndex', 'target', '#F59E0B', 'ui-crm', $crm, 10),
            $this->menu('CRM', 'proposals', 'Propostas', 'Comercial', 'ProposalsIndex', 'file-text', '#3B82F6', 'ui-crm', $crm, 20),
            $this->menu('CRM', 'contracts', 'Contratos', 'Comercial', 'ContractsIndex', 'briefcase', '#10B981', 'ui-contracts', $crm, 30),
            $this->menu('CRM', 'prospects', 'Prospects', 'Comercial', 'ProspectsIndex', 'users', '#10B981', 'ui-customers', $crm, 40),
            $this->menu('CRM', 'clients', 'Clientes', 'Comercial', 'ClientsIndex', 'users', '#3B82F6', 'ui-customers', $crm, 50),
            $this->menu('CRM', 'commissions', 'Comissoes', 'Comercial', 'ComissionsPage', 'trending-up', '#10B981', 'ui-crm', $crm, 60),
            $this->menu('CRM', 'crm_settings', 'Configurador geral', 'Configuracoes', 'GeneralSettings', 'settings', '#64748B', 'ui-crm', $admin, 110),

            $this->menu('POS', 'orders', 'Pedidos', 'Operacao', 'OrderHistoryPage', 'shopping-bag', '#0EA5E9', 'ui-orders', $human, 10),
            $this->menu('POS', 'cash_register', 'Caixa', 'Operacao', 'CashRegisterIndex', 'credit-card', '#4682B4', 'ui-orders', $human, 20),
            $this->menu('POS', 'prints', 'Impressoes', 'Operacao', 'PrintQueuePage', 'printer', '#0F766E', 'ui-orders', $human, 30),

            $this->menu('DELIVERY', 'orders', 'Pedidos', 'Operacao', 'OrderHistoryPage', 'shopping-bag', '#0EA5E9', 'ui-orders', $human, 10),
            $this->menu('DELIVERY', 'prints', 'Impressoes', 'Operacao', 'PrintQueuePage', 'printer', '#0F766E', 'ui-orders', $human, 20),

            $this->menu('PPC', 'displays', 'Displays', 'Producao', 'DisplayList', 'monitor', '#7C3AED', 'ui-ppc', $human, 10),

            $this->menu('SHOP', 'shop_home', 'Loja', 'Loja', 'ShopIndex', 'shopping-bag', '#0EA5E9', 'ui-shop', ['client'], 10),
            $this->menu('SHOP', 'franchises', 'Franquias', 'Loja', 'ShopFranchiseLocatorPage', 'map-pin', '#16A34A', 'ui-shop', ['client'], 20),
            $this->menu('SHOP', 'loyalty', 'Fidelidade', 'Loja', 'ShopLoyaltyPage', 'gift', '#F59E0B', 'ui-shop', ['client'], 30),
            $this->menu('SHOP', 'cart', 'Carrinho', 'Loja', 'ShopCartPage', 'shopping-cart', '#0EA5E9', 'ui-shop', ['client'], 40),
            $this->menu('SHOP', 'orders', 'Meus pedidos', 'Loja', 'ShopOrdersPage', 'package', '#7C3AED', 'ui-shop', ['client'], 50),
            $this->menu('SHOP', 'profile', 'Perfil', 'Loja', 'ShopProfilePage', 'user', '#64748B', 'ui-shop', ['client'], 60),
            $this->menu('SHOP', 'cards', 'Cartoes', 'Loja', 'ShopCardsPage', 'credit-card', '#0F766E', 'ui-shop', ['client'], 70),
            $this->menu('SHOP', 'download', 'Baixar cardapio', 'Loja', 'ShopDownloadPage', 'download', '#475569', 'ui-shop', ['client'], 80),
        ];
    }

    /**
     * @param array<int, string> $linkTypes
     * @param array<string, mixed>|null $routeParams
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
        int $sortOrder,
        ?array $routeParams = null
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
            'sortOrder',
            'routeParams'
        );
    }

    /**
     * @param array<string, mixed> $seed
     */
    private function seedMenu(array $seed): void
    {
        $routeParams = isset($seed['routeParams'])
            ? json_encode($seed['routeParams'], JSON_UNESCAPED_SLASHES)
            : null;

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
                'routeParams' => $routeParams,
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
}
