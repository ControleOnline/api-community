<?php

declare(strict_types=1);

// Read-only preflight for task #1496. Never boots the API kernel or migration runner.
use Composer\InstalledVersions;
use ControleOnline\Service\DatabaseSwitchService;
use Doctrine\DBAL\DriverManager;

require getcwd().'/config/bootstrap.php';

try {
    $connection = DriverManager::getConnection([
        'driver' => $_ENV['DATABASE_DRIVER'],
        'host' => $_ENV['DATABASE_HOST'],
        'port' => $_ENV['DATABASE_PORT'],
        'dbname' => $_ENV['DATABASE_NAME'],
        'user' => $_ENV['DATABASE_USER'],
        'password' => $_ENV['DATABASE_PASSWORD'],
        'charset' => 'utf8mb4',
    ]);
    $registryDatabase = $connection->fetchOne('SELECT DATABASE()');
    if ($registryDatabase !== 'co_staging') {
        // Follow the same domain-to-tenant resolver used by the staging API.
        (new DatabaseSwitchService($connection))->switchDatabaseByDomain('s.controleonline.com');
    }
    $actualDatabase = $connection->fetchOne('SELECT DATABASE()');
    if ($actualDatabase !== 'co_staging') {
        throw new RuntimeException('Staging domain does not resolve to the authorized co_staging database.');
    }
    $columns = $connection->fetchAllAssociative(
        "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
         AND ((TABLE_NAME = 'category' AND COLUMN_NAME IN ('text_color', 'icon_color'))
         OR (TABLE_NAME = 'routes' AND COLUMN_NAME IN ('text_color', 'icon_color', 'icon_background_color')))
         ORDER BY TABLE_NAME, COLUMN_NAME"
    );
    $valid = count($columns) === 5;
    foreach ($columns as $column) {
        $valid = $valid && $column['DATA_TYPE'] === 'varchar'
            && (int) $column['CHARACTER_MAXIMUM_LENGTH'] === 50 && $column['IS_NULLABLE'] === 'YES';
    }
    $version = InstalledVersions::getPrettyVersion('controleonline/common');
    echo json_encode([
        'authorizedDatabase' => $actualDatabase,
        'commonVersion' => $version,
        'commonReference' => InstalledVersions::getReference('controleonline/common'),
        'rootPins' => array_intersect_key(json_decode(file_get_contents(getcwd().'/composer.json'), true)['require'], array_flip(['controleonline/common', 'controleonline/orders', 'controleonline/financial'])),
        'installedCompanions' => ['orders' => InstalledVersions::getPrettyVersion('controleonline/orders'), 'financial' => InstalledVersions::getPrettyVersion('controleonline/financial')],
        'optionalColumns' => count($columns),
        'schemaReady' => $valid,
        'migrationRegistered' => (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM doctrine_migration_versions WHERE version = ?',
            ['DoctrineMigrations\\Common\\Version20261110220000']
        ) === 1,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    if (getenv('APPEARANCE_REQUIRE_SCHEMA') && !$valid) {
        throw new RuntimeException('The five authorized optional columns must exist before API activation.');
    }
    if (getenv('APPEARANCE_REQUIRE_PACKAGE') && (ltrim((string) $version, 'v') !== '1.0.11' || InstalledVersions::getReference('controleonline/common') !== '51a461d323779828745601cfe9c156f29b2e0626')) {
        throw new RuntimeException('The reviewed stable common 1.0.11 package is not installed.');
    }
} catch (Throwable $error) {
    // Connection errors may contain credentials or host details; never print their message.
    fwrite(STDERR, 'Staging appearance preflight failed: '.get_class($error).PHP_EOL);
    exit(1);
}
