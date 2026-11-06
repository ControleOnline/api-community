<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\{QueryCollectionExtensionInterface, QueryItemExtensionInterface};
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Service\ThemeAccessService;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\QueryBuilder;

final class ThemeSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private ThemeAccessService $access, private PeopleRoleService $roles) {}
    private function filter(QueryBuilder $query, QueryNameGeneratorInterface $names, string $class, ?Operation $operation): void
    {
        // Public domain CSS is the existing appearance transport, not a theme catalog.
        if ($class !== Theme::class || $operation?->getUriTemplate() === '/themes-colors.css' || $this->roles->isSuperAdmin()) return;
        $ids = $this->access->companyThemeIds();
        if ($ids === []) {$query->andWhere('1 = 0');return;}
        $parameter = $names->generateParameterName('allowedThemes');
        $query->andWhere($query->getRootAliases()[0].'.id IN (:'.$parameter.')')->setParameter($parameter, $ids);
    }
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    { $this->filter($queryBuilder, $queryNameGenerator, $resourceClass, $operation); }
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    { $this->filter($queryBuilder, $queryNameGenerator, $resourceClass, $operation); }
}
