<?php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use App\Resource\GetSetting;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

final class GetSettingItemDataProvider implements
  ItemDataProviderInterface, RestrictedDataProviderInterface
{
  public function __construct(EntityManagerInterface $manager)
  {
    $this->manager = $manager;
  }

  public function supports(
    string $resourceClass,
    string $operationName = null,
    array  $context = []
  ): bool
  {
    return GetSetting::class === $resourceClass;
  }

  public function getItem(
    string $resourceClass, $id, string $operationName = null, array $context = []
  ): ?GetSetting
  {
    if ($this->manager->find(People::class, $id) === null) {
      return null;
    }

    $setting = new GetSetting;

    $setting->id = $id;

    return $setting;
  }
}
