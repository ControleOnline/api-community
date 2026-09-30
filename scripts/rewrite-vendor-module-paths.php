<?php
/**
 * Packagist packages still publish config with modules/controleonline paths.
 * After composer install, rewrite every occurrence under vendor/controleonline
 * to vendor/controleonline so Doctrine/API Platform resolve correctly.
 *
 * Idempotent. Safe to run multiple times.
 */
$root = dirname(__DIR__);
$vendorRoot = $root . '/vendor/controleonline';

if (!is_dir($vendorRoot)) {
    fwrite(STDERR, "rewrite-vendor-module-paths: no vendor/controleonline — skip\n");
    exit(0);
}

$from = 'modules/controleonline';
$to = 'vendor/controleonline';
$changed = 0;
$files = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['yaml', 'yml', 'xml', 'php', 'json', 'md'], true)) {
        continue;
    }
    $contents = file_get_contents($path);
    if ($contents === false || strpos($contents, $from) === false) {
        continue;
    }
    $new = str_replace($from, $to, $contents);
    if ($new === $contents) {
        continue;
    }
    if (file_put_contents($path, $new) === false) {
        fwrite(STDERR, "rewrite-vendor-module-paths: failed writing $path\n");
        exit(1);
    }
    $changed++;
    $files++;
    fwrite(STDOUT, "rewrote: " . substr($path, strlen($root) + 1) . "\n");
}

fwrite(STDOUT, "rewrite-vendor-module-paths: done ($files files)\n");
exit(0);
