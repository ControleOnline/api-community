<?php

use App\Rector\GroupsToContextRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Doctrine\Set\DoctrineSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/modules/controleonline/multi-tenancy/src/Entity',
        __DIR__ . '/modules/controleonline/products/src/Entity',
        __DIR__ . '/modules/controleonline/common/src/Entity',
        __DIR__ . '/modules/controleonline/financial/src/Entity',
        __DIR__ . '/modules/controleonline/contract/src/Entity',
        __DIR__ . '/modules/controleonline/dashboard/src/Entity',
        __DIR__ . '/modules/controleonline/ead/src/Entity',
        __DIR__ . '/modules/controleonline/orders/src/Entity',
        __DIR__ . '/modules/controleonline/people/src/Entity',
        __DIR__ . '/modules/controleonline/queue/src/Entity',
        __DIR__ . '/modules/controleonline/logistic/src/Entity',
        __DIR__ . '/modules/controleonline/users/src/Entity',
        __DIR__ . '/modules/controleonline/tasks/src/Entity',
        __DIR__ . '/modules/controleonline/accounting/src/Entity',
    ]);

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);

    //$rectorConfig->rule(GroupsToContextRector::class);

    $rectorConfig->skip([
        // Exemplo: __DIR__ . '/modules/controleonline/some-module/src/Entity/SpecificEntity.php',
    ]);

    $rectorConfig->importNames();
};
