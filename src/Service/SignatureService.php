<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Library\Provider\Signature\SignatureFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\People;
use App\Entity\Config;
use App\Entity\PeopleDomain;

class SignatureService
{
  private $manager;
  private $defaultCompany;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
  }

  public function getFactory(?string $factoryName = null): ?SignatureFactory
  {
    $providerName = $factoryName === null ?
      $this->getDefaultProviderFromConfig() : $factoryName;

    if ($providerName === null) {
      return null;
    }

    $provider = sprintf(
      '\\App\\Library\\Provider\\Signature\\%s\\Factory',
      ucfirst(strtolower($providerName))
    );

    if (!class_exists($provider)) {
      throw new \Exception('Signature provider factory not found');
    }

    return new $provider(
      $this->getProviderConfig($providerName)
    );
  }
  public function setDefaultCompany(People $defaultCompany)
  {
    $this->defaultCompany = $defaultCompany;
  }

  private function getDefaultCompany(string $domain = null): ?People
  {

    if ($this->defaultCompany !== null)
      return $this->defaultCompany;

    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain ?: $_SERVER['HTTP_HOST']]);

    if ($company === null)
      return null;

    return $company->getPeople();
  }

  private function getProviderConfig(string $providerName): ?array
  {

    $myCompany = $this->getDefaultCompany();
    if ($myCompany instanceof People) {

      return $this->manager->getRepository(Config::class)
        ->getKeyValuesByPeople(
          $myCompany,
          strtolower($providerName)
        );
    }

    throw new \Exception('Company not found');
  }

  private function getDefaultProviderFromConfig(): ?string
  {
    $myCompany = $this->getDefaultCompany();
    if ($myCompany instanceof People) {
      $configs = $this->manager->getRepository(Config::class)
        ->getKeyValuesByPeople(
          $myCompany,
          'provider'
        );

      if ($configs === null) {
        return null;
      }

      return isset($configs['provider-signature']) ?
        $configs['provider-signature'] : null;
    }

    throw new \Exception('Company not found');
  }
}
