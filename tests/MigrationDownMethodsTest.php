<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class MigrationDownMethodsTest extends TestCase
{
    public function testTenantMigrationDownMethodsAreNoOps(): void
    {
        $migrationFiles = glob(dirname(__DIR__) . '/modules/controleonline/*/migrations/Version*.php') ?: [];
        sort($migrationFiles);

        self::assertNotEmpty($migrationFiles);

        $violations = [];
        foreach ($migrationFiles as $migrationFile) {
            $contents = (string) file_get_contents($migrationFile);
            $downBody = $this->extractDownMethodBody($contents);

            if ($downBody === null) {
                continue;
            }

            if (trim($downBody) !== 'return;') {
                $violations[] = str_replace(dirname(__DIR__) . '/', '', $migrationFile);
            }
        }

        self::assertSame([], $violations, 'Migration down() methods must remain no-op.');
    }

    private function extractDownMethodBody(string $contents): ?string
    {
        if (
            preg_match(
                '/public\s+function\s+down\s*\(\s*Schema\s+\$schema\s*\)\s*:\s*void\s*\{/',
                $contents,
                $match,
                PREG_OFFSET_CAPTURE
            ) !== 1
        ) {
            return null;
        }

        $openBrace = $match[0][1] + strlen($match[0][0]) - 1;
        $depth = 0;
        $length = strlen($contents);

        for ($index = $openBrace; $index < $length; ++$index) {
            if ($contents[$index] === '{') {
                ++$depth;
                continue;
            }

            if ($contents[$index] !== '}') {
                continue;
            }

            --$depth;
            if ($depth === 0) {
                return substr($contents, $openBrace + 1, $index - $openBrace - 1);
            }
        }

        return null;
    }
}
