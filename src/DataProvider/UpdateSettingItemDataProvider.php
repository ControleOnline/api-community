<?php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use App\Resource\UpdateSetting;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateSettingItemDataProvider implements
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
    return UpdateSetting::class === $resourceClass;
  }

  public function getItem(
    string $resourceClass, $id, string $operationName = null, array $context = []
  ): ?UpdateSetting
  {
    if ($this->manager->find(People::class, $id) === null) {
      return null;
    }

    $setting = new UpdateSetting;

    $setting->id = $id;

    return $setting;
  }
}
