<?php

namespace App\Controller;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchPeopleAction
{


  /**
   * peopleSalesman repository
   *
   * @var \ControleOnline\Repository\PeopleRepository
   */
  private $people;

  public function __construct(Security $security, EntityManagerInterface $entityManager)
  {
    $this->security       = $security;
    $this->people = $entityManager->getRepository(People::class);
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $myCompanies = [];

      $input = $request->get('input', false);
      $limit = $request->get('limit', 50);

      $companies   = $this->people->search($input,$limit);

      foreach ($companies as $company) {
        $myCompanies[] = [
          'id'         => $company['id'],
          'alias'      => $company['alias'],
          'name'      => $company['name'],
          'people_type'      => $company['people_type'],
          'enable'      => $company['enable'],
          'logo'       => $this->getCompanyLogo($company),
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
