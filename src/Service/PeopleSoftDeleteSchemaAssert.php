<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Runbook helper for api-community#83: expected people soft-delete columns.
 */
final class PeopleSoftDeleteSchemaAssert
{
    public const REQUIRED_COLUMNS = ['deleted', 'deleted_at'];

    /**
     * @param list<string> $columnNames
     * @return list<string>
     */
    public static function missingColumns(array $columnNames): array
    {
        $normalized = [];
        foreach ($columnNames as $name) {
            $normalized[] = strtolower((string) $name);
        }

        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $normalized, true)) {
                $missing[] = $required;
            }
        }

        return $missing;
    }
}
