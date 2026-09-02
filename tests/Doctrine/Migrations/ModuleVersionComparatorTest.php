<?php

declare(strict_types=1);

namespace App\Tests\Doctrine\Migrations;

use App\Doctrine\Migrations\ModuleVersionComparator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

final class ModuleVersionComparatorTest extends TestCase
{
    public function testPeopleSoftDeleteRunsBeforeLaterControleOnlineNamespace(): void
    {
        $comparator = new ModuleVersionComparator();
        $people = new Version('DoctrineMigrations\\People\\Version20260820030000');
        $other = new Version('ControleOnline\\Migrations\\Version20260820080000');

        self::assertLessThan(0, $comparator->compare($people, $other));
        self::assertGreaterThan(0, $comparator->compare($other, $people));
    }

    public function testSameTimestampFallsBackToModuleOrder(): void
    {
        $comparator = new ModuleVersionComparator();
        $people = new Version('DoctrineMigrations\\People\\Version20260820010000');
        $common = new Version('DoctrineMigrations\\Common\\Version20260820010000');

        self::assertGreaterThan(0, $comparator->compare($people, $common));
    }
}
