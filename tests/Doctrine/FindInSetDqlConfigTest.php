<?php

declare(strict_types=1);

namespace App\Tests\Doctrine;

use PHPUnit\Framework\TestCase;

final class FindInSetDqlConfigTest extends TestCase
{
    public function testDoctrineYamlRegistersFindInSetStringFunction(): void
    {
        $root = dirname(__DIR__, 2);
        $yaml = file_get_contents($root . '/config/packages/doctrine.yaml');

        self::assertIsString($yaml);
        self::assertMatchesRegularExpression(
            '/string_functions:\s*\n(?:[ \t]+.+\n)*[ \t]+find_in_set:\s+DoctrineExtensions\\\\Query\\\\Mysql\\\\FindInSet/',
            $yaml,
            'doctrine.orm.dql.string_functions must register find_in_set'
        );
        self::assertStringContainsString(
            'DoctrineExtensions\\Query\\Mysql\\FindInSet',
            $yaml
        );
    }

    public function testFindInSetClassExists(): void
    {
        self::assertTrue(
            class_exists(\DoctrineExtensions\Query\Mysql\FindInSet::class),
            'beberlei FindInSet must be autoloadable'
        );
    }
}
