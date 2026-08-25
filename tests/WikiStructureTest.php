<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class WikiStructureTest extends TestCase
{
    public function testWikiPointerContainsOnlyFullGitHubUrl(): void
    {
        $root = dirname(__DIR__);

        self::assertSame(
            "https://github.com/ControleOnline/api-community/wiki\n",
            file_get_contents($root . '/docs/wiki.md')
        );
    }

    public function testDocsWikiIsNotConfiguredAsSubmodule(): void
    {
        $root = dirname(__DIR__);
        $gitmodules = file_get_contents($root . '/.gitmodules');

        self::assertIsString($gitmodules);
        self::assertDoesNotMatchRegularExpression('/path\s*=\s*docs\/wiki/', $gitmodules);
        self::assertDoesNotMatchRegularExpression('/api-community\.wiki\.git/', $gitmodules);
    }
}
