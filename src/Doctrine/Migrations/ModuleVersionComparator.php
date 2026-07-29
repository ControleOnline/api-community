<?php

declare(strict_types=1);

namespace App\Doctrine\Migrations;

use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

use function preg_match;
use function strcmp;

final class ModuleVersionComparator implements Comparator
{
    private const MODULE_ORDER = [
        'Common' => 10,
        'People' => 20,
        'Contract' => 30,
        'Products' => 40,
        'Orders' => 50,
        'Financial' => 60,
        'Accounting' => 70,
        'Logistic' => 80,
        'Integration' => 90,
        'Queue' => 100,
        'Tasks' => 110,
        'Ead' => 120,
        'Users' => 130,
    ];

    public function compare(Version $a, Version $b): int
    {
        $moduleA = $this->extractModule((string) $a);
        $moduleB = $this->extractModule((string) $b);

        $orderA = self::MODULE_ORDER[$moduleA] ?? PHP_INT_MAX;
        $orderB = self::MODULE_ORDER[$moduleB] ?? PHP_INT_MAX;

        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }

        return strcmp((string) $a, (string) $b);
    }

    private function extractModule(string $version): string
    {
        if (preg_match('/^DoctrineMigrations\\\\([^\\\\]+)\\\\/', $version, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}

