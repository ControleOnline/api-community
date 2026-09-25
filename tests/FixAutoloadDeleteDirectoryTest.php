<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/src/FixAutoload.php';

final class FixAutoloadDeleteDirectoryTest extends TestCase
{
    public function testSourceModuleResolutionIsEnabledOnlyInDevelopment(): void
    {
        $original = getenv('APP_ENV');

        try {
            putenv('APP_ENV=dev');
            self::assertTrue(\App\FixAutoload::shouldUseSourceModules());

            putenv('APP_ENV=prod');
            self::assertFalse(\App\FixAutoload::shouldUseSourceModules());
            \App\FixAutoload::postInstall();
            self::assertTrue(
                is_dir(dirname(__DIR__) . '/vendor/controleonline/common')
                || is_dir(dirname(__DIR__) . '/modules/controleonline/common/src')
            );
        } finally {
            if ($original === false) {
                putenv('APP_ENV');
            } else {
                putenv('APP_ENV=' . $original);
            }
        }
    }

    public function testDeleteDirectoryUnlinksDirectorySymlinkWithoutDeletingItsTarget(): void
    {
        $root = sys_get_temp_dir() . '/fix-autoload-' . bin2hex(random_bytes(8));
        $target = $root . '/target';
        $link = $root . '/linked';

        self::assertTrue(mkdir($target, 0777, true));
        self::assertNotFalse(file_put_contents($target . '/keep.txt', 'keep'));

        if (!symlink($target, $link)) {
            \App\FixAutoload::deleteDirectory($target);
            rmdir($root);
            self::markTestSkipped('Directory symlinks are not available on this platform.');
        }

        try {
            \App\FixAutoload::deleteDirectory($link);

            self::assertFalse(is_link($link));
            self::assertSame('keep', file_get_contents($target . '/keep.txt'));
        } finally {
            \App\FixAutoload::deleteDirectory($target);
            if (is_dir($root)) {
                rmdir($root);
            }
        }
    }

    public function testDeleteDirectoryRemovesNestedFilesAndDirectories(): void
    {
        $root = sys_get_temp_dir() . '/fix-autoload-' . bin2hex(random_bytes(8));
        $nested = $root . '/nested';

        self::assertTrue(mkdir($nested, 0777, true));
        self::assertNotFalse(file_put_contents($nested . '/.hidden', 'hidden'));
        self::assertNotFalse(file_put_contents($root . '/file.txt', 'file'));

        \App\FixAutoload::deleteDirectory($root);

        self::assertDirectoryDoesNotExist($root);
    }
}
