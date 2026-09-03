<?php

declare(strict_types=1);

namespace App\Doctrine\Migrations;

use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

use function preg_match;
use function strcmp;

/**
 * Order tenant migrations by timestamp first, then module.
 *
 * Default FQCN strcmp hides DoctrineMigrations\People\* after
 * ControleOnline\Migrations\* (C < D). That made Version20260820030000
 * look "out of order" / skipped on mixed-namespace tenants (api-community#83).
 */
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
        'ZTenantSeed' => 900,
    ];

    public function compare(Version $a, Version $b): int
    {
        $timeA = $this->extractTimestamp((string) $a);
        $timeB = $this->extractTimestamp((string) $b);

        if ($timeA !== $timeB) {
            return $timeA <=> $timeB;
        }

        $moduleA = $this->extractModule((string) $a);
        $moduleB = $this->extractModule((string) $b);

        $orderA = self::MODULE_ORDER[$moduleA] ?? PHP_INT_MAX;
        $orderB = self::MODULE_ORDER[$moduleB] ?? PHP_INT_MAX;

        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }

        return strcmp((string) $a, (string) $b);
    }

    private function extractTimestamp(string $version): string
    {
        if (preg_match('/Version(\d{14})/', $version, $matches) === 1) {
            return $matches[1];
        }

        return $version;
    }

    private function extractModule(string $version): string
    {
        if (preg_match('/^DoctrineMigrations\\\\([^\\\\]+)\\\\/', $version, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
