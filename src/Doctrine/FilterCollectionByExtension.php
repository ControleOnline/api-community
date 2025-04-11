<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation; 
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FilterCollectionByExtension
implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass, 'collection');
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass, 'justoneitem');
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, string $applyTo): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $this->execute($resourceClass, $queryBuilder, $resourceClass, $applyTo, $rootAlias);
    }

    private function execute(string $class, QueryBuilder $queryBuilder, ?string $resourceClass = null, ?string $applyTo = null, ?string $rootAlias = null)
    {
        $serviceName = str_replace('Entity', 'Service', $class) . 'Service';
        $method = 'securityFilter';
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);
            if (method_exists($service, $method)) {
                $service->$method($queryBuilder, $resourceClass, $applyTo, $rootAlias);
            }
        }
    }
}