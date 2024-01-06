<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class VersionTeste extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS `teste_migrate` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `carrier_id` int(11) NOT NULL,
            `integration_type` enum(\'correios\',\'jadlog\',\'ssw\') DEFAULT NULL,
            `integration_user` varchar(100) DEFAULT NULL,
            `integration_password` varchar(100) DEFAULT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 0,
            `average_rating` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `carrier_integration_ibfk_1` (`carrier_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
