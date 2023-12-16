<?php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ControleOnline\Entity\CepAddress;

final class CepAddressCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return CepAddress::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null): array
    {
        return [];
    }
}
