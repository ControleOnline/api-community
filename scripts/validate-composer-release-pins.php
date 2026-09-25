<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$lock = json_decode((string) file_get_contents($root . '/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
$lockedPackages = [];
foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
    $lockedPackages[$package['name']] = $package;
}

$failures = [];
foreach ($manifest['require'] as $name => $version) {
    if (!str_starts_with($name, 'controleonline/')) {
        continue;
    }

    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        $failures[] = "$name must use an exact stable semantic version, got $version";
        continue;
    }

    $package = $lockedPackages[$name] ?? null;
    if ($package === null) {
        $failures[] = "$name is missing from composer.lock";
        continue;
    }

    if (ltrim($package['version'], 'v') !== $version) {
        $failures[] = "$name requires $version but composer.lock contains {$package['version']}";
        continue;
    }

    $reference = $package['dist']['reference'] ?? $package['source']['reference'] ?? '';
    if (!preg_match('/^[0-9a-f]{40}$/i', $reference)) {
        $failures[] = "$name has no immutable commit reference in composer.lock";
        continue;
    }

}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "All controleonline requirements are exact stable versions with immutable lock references." . PHP_EOL;
