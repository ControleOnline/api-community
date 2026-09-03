<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PeopleSoftDeleteSchemaAssert;
use PHPUnit\Framework\TestCase;

final class PeopleSoftDeleteSchemaAssertTest extends TestCase
{
    public function testMissingBothColumns(): void
    {
        self::assertSame(['deleted', 'deleted_at'], PeopleSoftDeleteSchemaAssert::missingColumns(['id', 'name']));
    }

    public function testAcceptsPresentColumnsCaseInsensitive(): void
    {
        self::assertSame([], PeopleSoftDeleteSchemaAssert::missingColumns(['ID', 'Deleted', 'DELETED_AT']));
    }
}
