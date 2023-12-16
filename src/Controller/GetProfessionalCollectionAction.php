<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\People;
use ControleOnline\Repository\PeopleRepository;
use App\Service\PeopleRoleService;

class GetProfessionalCollectionAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Request
   *
   * @var Request
   */
  private $request  = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * Professional repository
   *
   * @var PeopleRepository
   */
  private $professionals = null;

  private $roles    = null;

  public function __construct(Security $security, EntityManagerInterface $entityManager, PeopleRoleService $roles)
  {
    $this->manager  = $entityManager;
    $this->security = $security;
    $this->professionals = $this->manager->getRepository(People::class);
    $this->roles    = $roles;
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {



      if (($company = $request->query->get('myProvider', null)) !== null) {
        $company = $this->manager->getRepository(People::class)->find($company);
      }
      
      $roles = $this->roles->getAllRolesByCompany($this->security->getUser()->getPeople(), $company);
      if (!in_array('super', $roles) && !in_array('franchisee', $roles) && !in_array('salesman', $roles)) {
        throw new \Exception('Access denied', 403);
      }

      // get params

      $search   = $request->query->get('search', null);
      $page     = $request->query->get('page', 1);
      $limit    = $request->query->get('limit', 10);
      $paginate = [
        'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
        'limit' => !is_numeric($limit) ? 10 : $limit
      ];
      $search   = [
        'search' => $search,
      ];

      if ($company instanceof People) {
        $search['company'] = $company;
      }

      $output = [
        'response' => [
          'data'    => [
            'members' => $this->professionals->getAllProfessionals($search, $paginate),
            'total'   => $this->professionals->getAllProfessionals($search, null, true)
          ],
          'success' => true,
        ],
      ];

      return new JsonResponse($output, 200);
    } catch (\Exception $e) {
      $output = [
        'response' => [
          'data'    => [],
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ];

      return new JsonResponse($output, $e->getCode() >= 400 ? $e->getCode() : 500);
    }
  }
}
