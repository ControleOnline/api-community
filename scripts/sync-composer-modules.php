<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$sourceRoot = $root . '/modules/controleonline';
$repositories = [
    'accounting' => 'api-platform-accounting',
    'common' => 'api-platform-common',
    'contract' => 'api-platform-contract',
    'ead' => 'api-platform-ead',
    'financial' => 'api-platform-financial',
    'integration' => 'api-platform-integration',
    'logistic' => 'api-platform-logistic',
    'messages-sdk' => 'messages-sdk-php',
    'multi-tenancy' => 'api-platform-multi-tenancy',
    'orders' => 'api-platform-orders',
    'people' => 'api-platform-people',
    'products' => 'api-platform-products',
    'queue' => 'api-platform-queue',
    'report' => 'api-platform-report',
    'smoke-tests-playground' => 'smoke-tests-playground',
    'spc-sdk' => 'spc-sdk-php',
    'tasks' => 'api-platform-tasks',
    'users' => 'api-platform-users',
    'websocket-server' => 'api-platform-websocket-server',
    'whatsapp-sdk' => 'whatsapp-sdk-php',
];

if (!is_dir($sourceRoot) && !mkdir($sourceRoot, 0777, true) && !is_dir($sourceRoot)) {
    throw new RuntimeException(sprintf('Cannot create source module directory: %s', $sourceRoot));
}

foreach (array_keys($manifest['require'] ?? []) as $package) {
    if (!str_starts_with($package, 'controleonline/')) {
        continue;
    }

    $name = substr($package, strlen('controleonline/'));
    $repository = $repositories[$name] ?? null;
    if ($repository === null) {
        throw new RuntimeException(sprintf('No approved source repository is configured for %s.', $package));
    }
    $destination = $sourceRoot . '/' . $name;
    if (file_exists($destination . '/.git')) {
        echo "present $name\n";
        continue;
    }

    if (is_dir($destination)) {
        $entries = scandir($destination);
        if ($entries !== false && count($entries) > 2) {
            throw new RuntimeException(sprintf('Refusing to replace non-Git source directory: %s', $destination));
        }
        rmdir($destination);
    }

    $command = ['git', 'clone', '--depth=1', '--single-branch', '--branch', 'dev', 'https://github.com/ControleOnline/' . $repository . '.git', $destination];
    $process = proc_open($command, [1 => STDOUT, 2 => STDERR], $pipes, $root);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException(sprintf('Unable to clone source module %s from %s.', $name, $repository));
    }
    echo "cloned $name\n";
}
