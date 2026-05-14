<?php
// ALEMAC // 2026/05/09 13:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509130000 extends AbstractMigration
{
    private const HUMAN_LINK_TYPES = ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales'];
    private const COMMERCIAL_LINK_TYPES = ['client', 'provider', 'franchisee'];

    public function getDescription(): string
    {
        return 'Remove commercial people links from menu role configuration';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('menu') || !$this->tableExists('menu_link_type')) {
            return;
        }

        $this->addSql(
            'DELETE FROM menu_link_type WHERE link_type IN (?)',
            [self::COMMERCIAL_LINK_TYPES],
            [\Doctrine\DBAL\ArrayParameterType::STRING]
        );

        foreach (self::HUMAN_LINK_TYPES as $linkType) {
            $this->addSql(
                "INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT menu.id, :linkType
                 FROM menu
                 WHERE menu.app_type = 'SHOP'
                 AND NOT EXISTS (
                    SELECT 1
                    FROM menu_link_type existing_link
                    WHERE existing_link.menu_id = menu.id
                    AND existing_link.link_type = :linkType
                 )",
                ['linkType' => $linkType]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Commercial menu link cleanup cannot be reverted safely.');
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
