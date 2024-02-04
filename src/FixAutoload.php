<?php

namespace App;

class FixAutoload
{


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
        self::replaceInComposerFiles();
        self::deleteDirectory(__DIR__ . '/../vendor/controleonline');
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
    }
}
