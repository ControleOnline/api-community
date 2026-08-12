<?php

namespace App\Library\Postalcode;

use App\Library\Postalcode\Entity\Address;
use App\Library\Postalcode\Exception\ProviderRequestException;
use App\Library\Postalcode\GoogleMaps\GoogleMapsServiceProvider;
use App\Library\Postalcode\Postmon\PostmonServiceProvider;
use App\Library\Postalcode\Viacep\ViacepServiceProvider;

/**
 * CEP lookup balancer.
 * Order (priority): Postmon → ViaCEP → Google Maps.
 */
class PostalcodeProviderBalancer
{
  private array $providers = [
    'postmon'    => PostmonServiceProvider::class,
    'viacep'     => ViacepServiceProvider::class,
    'googlemaps' => GoogleMapsServiceProvider::class,
  ];

  private ?string $currentProviderKey = null;
  private $currentProvider = null;

  public function search(string $postalCode): Address
  {
    $postalCode = preg_replace('/\D+/', '', $postalCode) ?? '';
    $keys = array_keys($this->providers);

    if ($this->currentProviderKey === null) {
      $this->currentProviderKey = $keys[0];
      $this->currentProvider = new $this->providers[$this->currentProviderKey]();
    }

    try {
      return $this->currentProvider->getAddress($postalCode);
    } catch (\Exception $e) {
      if ($e instanceof ProviderRequestException || $e instanceof \Exception) {
        $this->setNextProvider();
        return $this->search($postalCode);
      }
      throw $e;
    }
  }

  public function getProviderCodeName(): string
  {
    return $this->currentProviderKey ?? (array_keys($this->providers)[0] ?? '');
  }

  private function setNextProvider(): void
  {
    $keys = array_keys($this->providers);
    $idx = array_search($this->currentProviderKey, $keys, true);
    $nextIdx = ($idx === false) ? 0 : $idx + 1;
    if ($nextIdx >= count($keys)) {
      throw new \Exception('Postalcode services are not available');
    }
    $this->currentProviderKey = $keys[$nextIdx];
    $this->currentProvider = new $this->providers[$this->currentProviderKey]();
  }
}
