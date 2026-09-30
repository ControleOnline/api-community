<?php

namespace App;

/**
 * Legacy helper from the git-submodule era.
 * Packagist installs live under vendor/controleonline — do NOT rewrite
 * autoload paths to modules/ or delete vendor packages.
 */
class FixAutoload
{
    private static $envVars;

    public function __construct()
    {
        self::$envVars = self::readEnvFile(__DIR__ . '/../.env.local');
    }

    private static function getPaths()
    {
        return [
            'vendor/composer/autoload_classmap.php',
            'vendor/composer/autoload_psr4.php',
            'vendor/composer/autoload_static.php',
            'vendor/composer/installed.php',
            'vendor/composer/installed.json',
        ];
    }

    /**
     * @deprecated No-op since Packagist migration. Kept so deploy scripts
     * that still call postInstall() do not break.
     */
    public static function postInstall()
    {
        // Intentionally empty: modules/controleonline/* are no longer the
        // source of truth; packages resolve from vendor/controleonline/*.
        error_log('App\\FixAutoload::postInstall is a no-op (Packagist vendor paths).');
    }

    private static function readEnvFile(string $filePath): array
    {
        $envVariables = [];
        if (!file_exists($filePath)) {
            return $envVariables;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line) || preg_match('/^\s*###/', $line)) {
                continue;
            }
            if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2], '"\'');
                $envVariables[$key] = $value;
            }
        }

        return $envVariables;
    }

    public static function deleteDirectory($path)
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            throw new \RuntimeException(sprintf('Unable to read directory during cleanup: %s', $path));
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            self::deleteDirectory($path . DIRECTORY_SEPARATOR . $entry);
        }

        if (!rmdir($path)) {
            throw new \RuntimeException(sprintf('Unable to remove directory during cleanup: %s', $path));
        }
    }
}
