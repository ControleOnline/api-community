<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Entity\PeopleSalesman;

class UserCompanyService
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  private $security;

  public function __construct(EntityManagerInterface $entityManager, Security $security)
  {
    $this->manager  = $entityManager;
    $this->security = $security;
  }

  public function getMyCompany(int $companyId = null): ?People
  {
    if ($companyId === null) {
      $companies = $this->security->getUser()->getPeople() ?
        $this->security->getUser()->getPeople()->getLink() : null;

      if (empty($companies) || $companies->first() === false)
        return null;

      return $companies->first()->getCompany();
    }

    $company = $this->manager->find(People::class, $companyId);

    if ($company instanceof People) {
      if (!$this->isMyCompany($company)) {
        return null;
      }
    }

    return $company;
  }

  public function isMyCompany(People $company): bool
  {
    return $this->security->getUser()->getPeople()->getLink()->exists(
      function ($key, $element) use ($company) {
        return $element->getCompany() === $company;
      }
    );
  }

  public function getMyProvider(int $providerId = null): ?People
  {
    $provider = $this->manager->getRepository(People::class)->find($providerId);
    if ($provider === null)
      return null;

    return $this->manager->getRepository(PeopleSalesman::class)
      ->companyIsMyProvider($this->security->getUser()->getPeople(), $provider) ? $provider : null;
  }
}
