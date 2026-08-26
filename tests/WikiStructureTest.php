<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class WikiStructureTest extends TestCase
{
    public function testDocsWikiIsConfiguredAsMainWikiSubmodule(): void
    {
        $root = dirname(__DIR__);
        $gitmodules = file_get_contents($root . '/.gitmodules');

        self::assertIsString($gitmodules);
        self::assertMatchesRegularExpression('/path\s*=\s*docs\/wiki/', $gitmodules);
        self::assertMatchesRegularExpression(
            '/url\s*=\s*https:\/\/github\.com\/ControleOnline\/api-community\.wiki\.git/',
            $gitmodules
        );
    }
}
