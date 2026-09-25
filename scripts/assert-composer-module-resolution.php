<?php

declare(strict_types=1);

$mode = $argv[1] ?? '';
$root = dirname(__DIR__);
$manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$autoload = $root . '/vendor/composer/autoload_psr4.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer PSR-4 autoload map is missing." . PHP_EOL);
    exit(1);
}

$contents = (string) file_get_contents($autoload);
if ($mode === 'production') {
    foreach ($manifest['require'] as $name => $_version) {
        if (str_starts_with($name, 'controleonline/') && !is_dir($root . '/vendor/' . $name)) {
            fwrite(STDERR, "Production package $name is not installed under vendor/." . PHP_EOL);
            exit(1);
        }
    }
    $paths = require $autoload;
    $sourceResolvedProductionNamespace = false;
    foreach ($paths as $namespace => $directories) {
        if (str_contains($namespace, '\\Tests\\')) {
            continue;
        }
        foreach ((array) $directories as $directory) {
            if (str_contains($directory, 'modules/controleonline')) {
                $sourceResolvedProductionNamespace = true;
                break 2;
            }
        }
    }
    if (!is_dir($root . '/vendor/controleonline/common')
        || !str_contains($contents, "\$vendorDir . '/controleonline/common/src'")
        || $sourceResolvedProductionNamespace) {
        fwrite(STDERR, "Production packages must resolve from vendor/controleonline." . PHP_EOL);
        exit(1);
    }
    echo "Production Composer modules resolve from vendor/." . PHP_EOL;
    exit(0);
}

if ($mode === 'development') {
    if (is_dir($root . '/vendor/controleonline') || !str_contains($contents, 'modules/controleonline')) {
        fwrite(STDERR, "Development Composer packages must resolve from modules/controleonline source checkouts." . PHP_EOL);
        exit(1);
    }
    foreach ($manifest['require'] as $name => $_version) {
        if (str_starts_with($name, 'controleonline/')) {
            $modulePath = $root . '/modules/controleonline/' . substr($name, strlen('controleonline/'));
            if (!is_dir($modulePath)) {
                fwrite(STDERR, "Development source checkout for $name is missing." . PHP_EOL);
                exit(1);
            }
        }
    }
    echo "Development Composer packages resolve from local source checkouts." . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "Expected resolution mode: production or development." . PHP_EOL);
exit(2);
