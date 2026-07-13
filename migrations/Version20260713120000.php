<?php
// ALEMAC // 2026/07/13 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create media_types and people_media tables for people media attachments';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('media_types')) {
            $this->addSql(
                "CREATE TABLE media_types (
                    id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
                    type VARCHAR(32) NOT NULL,
                    people_type SET('F','J') COLLATE utf8_unicode_ci NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY media_type_unique (type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
            );
        }

        $this->addSql(
            "INSERT INTO media_types (id, type, people_type) VALUES
                (1, 'logo', 'J'),
                (2, 'icon', 'J'),
                (3, 'pin', 'J'),
                (4, 'stamp', 'J'),
                (5, 'avatar', 'F'),
                (6, 'background', 'J')
             ON DUPLICATE KEY UPDATE
                type = VALUES(type),
                people_type = VALUES(people_type)"
        );

        if ($this->tableExists('people_media')) {
            return;
        }

        $this->addSql(
            "CREATE TABLE people_media (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                people_id INT(11) NOT NULL,
                file_id INT(11) NOT NULL,
                media_type_id SMALLINT(5) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY people_id_2 (people_id, file_id, media_type_id),
                KEY people_id (people_id),
                KEY file_id (file_id),
                KEY media_type_id (media_type_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
        );

        $this->addSql(
            'ALTER TABLE people_media
             ADD CONSTRAINT people_media_ibfk_1
             FOREIGN KEY (people_id) REFERENCES people (id)
             ON DELETE CASCADE ON UPDATE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE people_media
             ADD CONSTRAINT people_media_ibfk_2
             FOREIGN KEY (file_id) REFERENCES files (id)
             ON DELETE CASCADE ON UPDATE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE people_media
             ADD CONSTRAINT people_media_ibfk_3
             FOREIGN KEY (media_type_id) REFERENCES media_types (id)
             ON UPDATE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('people_media')) {
            $this->addSql('DROP TABLE people_media');
        }

        if ($this->tableExists('media_types')) {
            $this->addSql('DROP TABLE media_types');
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
