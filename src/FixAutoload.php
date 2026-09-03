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
        //if (isset(self::$envVars['APP_ENV']) && self::$envVars['APP_ENV'] === 'dev')
        self::replaceInComposerFiles();
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
        if (is_dir($path)) {
            $files = glob($path . '/{.,}*', GLOB_BRACE);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    if (basename($file) !== '.' && basename($file) !== '..') {
                        self::deleteDirectory($file);
                    }
                } else {
                    unlink($file);
                }
            }
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
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
