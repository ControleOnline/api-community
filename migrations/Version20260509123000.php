<?php
// ALEMAC // 2026/05/09 12:30:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509123000 extends AbstractMigration
{
    private const CURRENT_MENU_ROUTES = [
        'FinancialHubPage',
        'IncomeStatement',
        'Payables',
        'Receivables',
        'OwnTransfers',
        'WalletsPage',
        'InvoiceCategoriesPage',
        'OrderHistoryPage',
        'ProvidersIndex',
        'CategoriesPage',
        'InventoriesPage',
        'PurchasingSuggestion',
        'PurchaseFormPage',
        'MenuCostsPage',
        'LabelsPage',
        'DisplayList',
        'ClientsIndex',
        'EmployeesIndex',
        'PdvPage',
        'LinkedOrderSettlementPage',
        'ModelTemplatesPage',
        'ConfiguratorPage',
        'MenuAccessConfigPage',
        'DevicesIndex',
        'ConnectionsPage',
        'IntegrationsPage',
        'ManagerCategoriesPage',
        'TranslationsReviewPage',
        'CrmIndex',
        'ProposalsIndex',
        'ContractsIndex',
        'ProspectsIndex',
        'ComissionsPage',
        'GeneralSettings',
        'CashRegisterIndex',
        'PrintQueuePage',
        'ShopIndex',
        'ShopFranchiseLocatorPage',
        'ShopLoyaltyPage',
        'ShopCartPage',
        'ShopOrdersPage',
        'ShopProfilePage',
        'ShopCardsPage',
        'ShopDownloadPage',
    ];

    public function getDescription(): string
    {
        return 'Remove legacy menu routes after APP_TYPE/link_type menu seed';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('menu') || !$this->tableExists('routes')) {
            return;
        }

        if ($this->tableExists('menu_link_type')) {
            $this->addSql("DELETE menu_link_type FROM menu_link_type INNER JOIN menu ON menu.id = menu_link_type.menu_id WHERE menu.menu_key LIKE 'legacy\\_%'");
        }

        if ($this->tableExists('menu_role')) {
            $this->addSql("DELETE menu_role FROM menu_role INNER JOIN menu ON menu.id = menu_role.menu_id WHERE menu.menu_key LIKE 'legacy\\_%'");
        }

        $this->addSql("DELETE FROM menu WHERE menu_key LIKE 'legacy\\_%'");

        $placeholders = implode(', ', array_fill(0, count(self::CURRENT_MENU_ROUTES), '?'));
        $this->addSql(
            "DELETE routes FROM routes
             LEFT JOIN menu ON menu.route_id = routes.id
             WHERE menu.id IS NULL
             AND routes.route NOT IN ($placeholders)",
            self::CURRENT_MENU_ROUTES
        );

        if ($this->tableExists('category')) {
            $this->addSql(
                "DELETE category FROM category
                 LEFT JOIN menu ON menu.category_id = category.id
                 WHERE category.context = 'menu'
                 AND menu.id IS NULL"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Legacy menu route cleanup cannot be reverted safely.');
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
