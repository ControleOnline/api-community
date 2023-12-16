<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;


use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Repository\PeopleRepository;
use App\Service\PeopleRoleService;

class GetCustomerCollectionAction
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
   * Roles
   *
   * @var PeopleRoleService
   */
  private $peopleRoles = null;

  /**
   * Customer repository
   *
   * @var CustomerRepository
   */
  private $customers = null;

  public function __construct(Security $security, EntityManagerInterface $entityManager, PeopleRoleService $roles)
  {
    $this->manager     = $entityManager;
    $this->security    = $security;
    $this->peopleRoles = $roles;
    $this->customers   = $this->manager->getRepository(People::class);
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $myPeople = $this->security->getUser()->getPeople();
      $providerId = $request->query->get('myProvider', null);

      $provider = null;

      if ($this->peopleRoles->isSuperAdmin($myPeople) || $this->peopleRoles->isFranchisee($myPeople)) {
        $provider = $this->getMyCompany((int) $providerId);
        if ($provider === null) {
          throw new \Exception('Company was not found', 404);
        }
      } else {

        $provider = $this->getMyProvider((int) $providerId);

        if ($provider === null) {
          throw new \Exception('Provider was not found', 404);
        }
      }

      // get params

      $type     = $request->query->get('type', null);
      $fromDate = $request->query->get('from', null);
      $toDate   = $request->query->get('to', null);
      $table    = $request->query->get('table', "customer");

      $myRoles   = $this->peopleRoles->getAllRolesByCompany($myPeople, $provider);

      $searchBy = $request->query->get('searchBy', null);

      if (preg_match('/[a-z();:|!"#$%&\/=?~^><ªº\\s-]/i', $searchBy)) {
        $numeric = preg_replace('/[.(),;:|!"#$%&\/=?~^><ªº\\s-]/', '', $searchBy);

        if (!is_numeric($numeric)) {
          $searchBy = preg_replace('/[.(),;:|!"#$%&\/=?~^><ªº\\s-]/', '%', $searchBy);
        } else {
          $searchBy = $numeric;
        }
      } else {
        if (preg_match("/[,]/i", $searchBy)) {
          $searchBy = preg_replace('/[,]/', '.', $searchBy);
        }
      }

      $page     = $request->query->get('page', 1);
      $limit    = $request->query->get('limit', 10);
      $paginate = [
        'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
        'limit' => !is_numeric($limit) ? 10 : $limit
      ];
      $search   = [
        'fromDate' => $fromDate,
        'toDate'   => $toDate,
        'search'   => $searchBy,
      ];

      $methods = [];
      // get query method
      foreach ($myRoles as $myRole) {
        $getMethod = sprintf('get%s%sCustomers', ucfirst(strtolower($myRole)), ucfirst(strtolower($type)));
        if (method_exists($this->customers, $getMethod)) {
          $methods[] = $getMethod;
        }
      }

      if (empty($methods)) {
        throw new \Exception(sprintf('Method "%s" was not found', $getMethod), 404);
      }

      $output = [
        'response' => [
          'data'    => [
            'members' => $myPeople !== null ? $this->customers->{$methods[0]}($table, $search, $provider, $myPeople, false, $paginate) : [],
            'total'   => $myPeople !== null ? $this->customers->{$methods[0]}($table, $search, $provider, $myPeople, true) : 0
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

  private function getMyCompany($companyId = null): ?People
  {
    if ($companyId === null) {
      $companies = $this->security->getUser()->getPeople() ?
        $this->security->getUser()->getPeople()->getPeopleCompany() : null;

      if (empty($companies) || $companies->first() === false)
        return null;

      return $companies->first()->getCompany();
    }

    $company = $this->manager->find(People::class, $companyId);

    if ($company instanceof People) {

      // verify if client is a company of current user

      $isMyCompany = $this->security->getUser()->getPeople()->getPeopleCompany()->exists(
        function ($key, $element) use ($company) {
          return $element->getCompany() === $company;
        }
      );

      if ($isMyCompany === false) {
        return null;
      }
    }

    return $company;
  }

  private function getMyProvider($providerId = null): ?People
  {
    if ($providerId === null) {
      return null;
    }

    $provider = $this->manager->getRepository(People::class)->find($providerId);

    return $this->manager->getRepository(PeopleSalesman::class)
      ->companyIsMyProvider($this->security->getUser()->getPeople(), $provider) ? $provider : ($this->getMyCompany($providerId) ?  $provider : null);
  }
}
