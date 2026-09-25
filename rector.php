<?php

use App\Rector\GroupsToContextRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Doctrine\Set\DoctrineSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/vendor/controleonline/multi-tenancy/src/Entity',
        __DIR__ . '/vendor/controleonline/products/src/Entity',
        __DIR__ . '/vendor/controleonline/common/src/Entity',
        __DIR__ . '/vendor/controleonline/financial/src/Entity',
        __DIR__ . '/vendor/controleonline/contract/src/Entity',
        __DIR__ . '/vendor/controleonline/report/src/Entity',
        __DIR__ . '/vendor/controleonline/ead/src/Entity',
        __DIR__ . '/vendor/controleonline/orders/src/Entity',
        __DIR__ . '/vendor/controleonline/people/src/Entity',
        __DIR__ . '/vendor/controleonline/queue/src/Entity',
        __DIR__ . '/vendor/controleonline/logistic/src/Entity',
        __DIR__ . '/vendor/controleonline/users/src/Entity',
        __DIR__ . '/vendor/controleonline/tasks/src/Entity',
        __DIR__ . '/vendor/controleonline/accounting/src/Entity',
    ]);

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);

    //$rectorConfig->rule(GroupsToContextRector::class);

    $rectorConfig->skip([
        // Exemplo: __DIR__ . '/vendor/controleonline/some-module/src/Entity/SpecificEntity.php',
    ]);

    $rectorConfig->importNames();
};
