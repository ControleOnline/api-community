<?php

namespace App\Controller;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

class GetMySaleCompaniesAction
{
  /*
   * @var Security
   */
  private $security;

  /**
   * peopleSalesman repository
   *
   * @var \ControleOnline\Repository\PeopleSalesmanRepository
   */
  private $peopleSalesman;

  public function __construct(Security $security, EntityManagerInterface $entityManager)
  {
    $this->security       = $security;
    $this->peopleSalesman = $entityManager->getRepository(PeopleSalesman::class);
  }

  public function __invoke(): JsonResponse
  {
    try {

      $myCompanies = [];

      /**
       * @var \ControleOnline\Entity\User
       */
      $currentUser = $this->security->getUser();

      /**
       * @var \ControleOnline\Entity\People
       */
      $userPeople  = $currentUser->getPeople();

      $companies   = $this->peopleSalesman->getMySaleCompanies($userPeople);

      foreach ($companies as $company) {
          $myCompanies[] = [
            'id'         => $company['people_id'],
            'alias'      => $company['people_alias'],
            'logo'       => $this->getCompanyLogo($company),
            'document'   => $company['people_document'],
            'commission' => $company['commission'],
          ];
      }

      return new JsonResponse([
        'response' => [
          'data'    => $myCompanies,
          'count'   => count($myCompanies),
          'error'   => '',
          'success' => true,
        ],
      ]);

    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getCompanyLogo(array $company): ?array
  {
    if (empty($company['file_id']))
      return null;

    return [
      'id'     => $company['file_id'],
      'domain' => $company['file_domain'],
      'url'    => $company['file_url'],
    ];
  }
}
