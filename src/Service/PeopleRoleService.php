<?php

namespace App\Service;

use App\Entity\People;
use App\Entity\PeopleClient;
use App\Entity\PeopleDomain;
use App\Entity\PeopleSalesman;
use App\Entity\PeopleEmployee;
use App\Entity\PeopleFranchisee;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\PeopleService;

class PeopleRoleService
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * People Service
   *
   * @var PeopleService
   */
  private $people  = null;

  public function __construct(
    EntityManagerInterface $entityManager,
    Security               $security,
    PeopleService          $peopleService
  ) {
    $this->manager  = $entityManager;
    $this->security = $security;
    $this->people   = $peopleService;
  }

  public function isFranchisee(People $people): bool
  {
    return in_array('franchisee', $this->getAllRoles($people));
  }

  public function isSuperAdmin(People $people): bool
  {
    return in_array('super', $this->getAllRoles($people));
  }

  public function isSalesman(People $people): bool
  {
    return in_array('salesman', $this->getAllRoles($people));
  }

  public function getAllRoles(People $people = null): array
  {

    $mainCompany = $this->getMainCompany();


    if ($people === null) {
      $people = $this->security->getUser()->getPeople();
      if (!($people instanceof People)) {
        return ['guest'];
      }
    } else {
      $peopleCompany = $people->getPeopleCompany()->first();
      if ($peopleCompany === false) {
        return ['guest'];
      }
    }

    $myCompany  = $peopleCompany->getCompany();
    return $this->getAllRolesByCompany($people, $myCompany);
  }

  public function getAllRolesByCompany(People $people, People $company): array
  {
    $peopleRole = [];
    $mainCompany = $this->getMainCompany();

    if ($company->getId() == $mainCompany->getId()) {
      $isSuper = $mainCompany->getPeopleEmployee()
        ->exists(
          function ($key, PeopleEmployee $peopleEmployee) use ($people) {
            return $peopleEmployee->getEmployee()->getId() === $people->getId();
          }
        );

      if ($isSuper) {
        $peopleRole[] = 'super';
      }
    }


    $isFranchisee = $mainCompany->getPeopleFranchisor()
      ->exists(
        function ($key, PeopleFranchisee $peopleFranchisee) use ($people, $company) {
          foreach ($peopleFranchisee->getFranchisee()->getPeopleEmployee() as $peopleEmployee) {
            if ($peopleEmployee->getCompany()->getId() == $company->getId() &&  $peopleEmployee->getEmployee()->getId() === $people->getId()) {
              return  true;
            }
          }
        }
      );

    if ($isFranchisee) {
      $peopleRole[] = 'franchisee';
      $peopleRole[] = 'admin';
    }

    $isClient = $company->getPeopleEmployee()
      ->exists(
        function ($key, PeopleEmployee $peopleEmployee) use ($people) {
          return $peopleEmployee->getEmployee()->getId() === $people->getId();
        }
      );

    if ($isClient) {
      $peopleRole[] = 'client';
    }


    $isClient = $people->getPeopleCompany()
      ->exists(
        function ($key, PeopleEmployee $peopleEmployee) use ($company) {
          return $this->manager->getRepository(PeopleClient::class)->findOneBy(
            [
              'client' => $peopleEmployee->getCompany(),
              'company_id' => $company->getId()
            ]
          );
        }
      );

    if ($isClient) {
      $peopleRole[] = 'client';
    }


    $isSalesman = $company->getPeopleSalesman()
      ->exists(
        function ($key, PeopleSalesman $peopleSalesman) use ($people) {
          return $peopleSalesman->getSalesman()->getId() === $people->getId();
        }
      );
    if ($isSalesman) {
      $peopleRole[] = 'salesman';
    }


    return array_unique(empty($peopleRole) ? ['guest'] : $peopleRole);
  }

  /**
   * Retorna a people da empresa principal segundo o dominio da api
   *
   * @return People
   */
  public function getMainCompany(): People
  {
    $domain  = $_SERVER['HTTP_HOST'];
    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      throw new \Exception(
        sprintf('Main company "%s" not found', $domain)
      );

    return $company->getPeople();
  }
}
