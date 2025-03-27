<?php

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FilterCollectionByExtension
implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{


  public function __construct(
    private Security $security,
    private ContainerInterface $container
  ) {}

  public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $resourceClass, $operationName = null)
  {
    $this->addWhere($queryBuilder, $resourceClass, 'collection');
  }

  public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $resourceClass, array $identifiers, $operationName = null, array $context = [])
  {
    $this->addWhere($queryBuilder, $resourceClass, 'justoneitem');
  }

  private function addWhere(QueryBuilder $queryBuilder, $resourceClass, $applyTo): void
  {
    /*
    if (empty($this->security->getUser()))
      return;

    if ($this->security->isGranted('ROLE_ADMIN')) {
      return;
    }
    */
    $rootAlias = $queryBuilder->getRootAliases()[0];

    $this->execute($resourceClass, $queryBuilder, $resourceClass, $applyTo, $rootAlias);
  }

  private function execute($class, QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null)
  {

    $serviceName = str_replace('Entity', 'Service', $class) . 'Service';
    $method = 'securityFilter';
    if ($this->container->has($serviceName)) {
      $service = $this->container->get($serviceName);
      if (method_exists($service, $method))
        $service->$method($queryBuilder, $resourceClass, $applyTo, $rootAlias);
    }
  }
}
