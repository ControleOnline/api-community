<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Doctrine\Migrations\ModuleVersionComparator;
use Doctrine\Migrations\Version\Comparator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DoctrineMigrationsComparatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('doctrine.migrations.dependency_factory')) {
            return;
        }

        $container
            ->getDefinition('doctrine.migrations.dependency_factory')
            ->addMethodCall('setService', [
                Comparator::class,
                new Reference(ModuleVersionComparator::class),
            ]);
    }
}

