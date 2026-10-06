<?php

declare(strict_types=1);

namespace App\Tests\Doctrine;

use PHPUnit\Framework\TestCase;

final class FindInSetDqlConfigTest extends TestCase
{
    public function testDoctrineYamlRegistersFindInSetStringFunction(): void
    {
        $root = dirname(__DIR__, 2);
        $config = \Symfony\Component\Yaml\Yaml::parseFile($root . '/config/packages/doctrine.yaml');
        self::assertSame(
            \DoctrineExtensions\Query\Mysql\FindInSet::class,
            $config['doctrine']['orm']['dql']['string_functions']['find_in_set'] ?? null,
            'doctrine.orm.dql.string_functions must register find_in_set'
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
