<?php

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Definir os diretórios de entidades em todos os módulos
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

    // Aplicar regras para converter anotações Doctrine para atributos
    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    // Incluir regras para PHP 8.3 (otimizado para sua versão)
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);

    // Ignorar arquivos ou pastas específicas, se necessário
    $rectorConfig->skip([
        // Exemplo: __DIR__ . '/modules/controleonline/some-module/src/Entity/SpecificEntity.php',
    ]);
};