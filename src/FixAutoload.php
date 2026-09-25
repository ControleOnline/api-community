<?php

namespace App;

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
            'vendor/composer/instaled.php',
            'vendor/composer/intaled.json'

        ];
    }

    public static function postInstall()
    {
        if (!self::shouldUseSourceModules()) {
            return;
        }

        self::replaceInComposerFiles();
    }

    public static function shouldUseSourceModules(): bool
    {
        $appEnv = getenv('APP_ENV');
        if ($appEnv === false || $appEnv === '') {
            $localEnvPath = __DIR__ . '/../.env.local';
            $localEnv = is_file($localEnvPath) ? self::readEnvFile($localEnvPath) : [];
            $appEnv = $localEnv['APP_ENV'] ?? 'prod';
        }

        return strtolower($appEnv) === 'dev';
    }

    private static function readEnvFile(string $filePath): array
    {
        $envVariables = [];
        if (!file_exists($filePath)) {
            error_log("Arquivo .env não encontrado: $filePath");
            return $envVariables;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line) || preg_match('/^\s*###/', $line)) {
                continue;
            }
            if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                $value = trim($value, '"\'');
                $envVariables[$key] = $value;
            }
        }

        return $envVariables;
    }

    public static function deleteDirectory($path)
    {
        // Composer may leave directory symlinks under vendor/. Treat links as
        // filesystem entries, not directories, or cleanup can follow a link
        // and delete files from the linked module checkout.
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

    private static function replaceInComposerFiles()
    {
        $path = '/../modules/controleonline';
        $classmapFiles = self::getPaths();
        foreach ($classmapFiles as $classmapFile) {
            if (file_exists($classmapFile)) {
                $classmapContent = file_get_contents($classmapFile);
                $modifiedContent = str_replace('/controleonline', $path, $classmapContent);
                file_put_contents($classmapFile, $modifiedContent);
            }
        }
        self::deleteDirectory(__DIR__ . '/../vendor/controleonline');
    }
}
