<?php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use App\Entity\CepAddress;
use App\Library\Postalcode\PostalcodeProviderBalancer;

final class CepAddressItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return CepAddress::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?CepAddress
    {
        $provider   = new PostalcodeProviderBalancer();
        $address    = $provider->search($id);
        $cepAddress = new CepAddress($id);

        $cepAddress->description = sprintf(
          '%s - %s, %s, %s, %s',
          $address->getStreet(),
          $address->getDistrict(),
          $address->getPostalCode(),
          $address->getUF(),
          $address->getCountry()
        );

        $cepAddress->country     = $address->getCountry();
        $cepAddress->state       = $address->getUF();
        $cepAddress->city        = $address->getCity();
        $cepAddress->district    = $address->getDistrict();
        $cepAddress->street      = $address->getStreet();
        $cepAddress->number      = $address->getNumber();
        $cepAddress->provider    = $provider->getProviderCodeName();

        return $cepAddress;
    }
}
