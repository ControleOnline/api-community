<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\SchoolClass;
use App\Entity\People;
use App\Entity\PeopleSalesman;
use App\Service\PeopleRoleService;

class GetSchoolProfessionalClassesCollectionAction
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

    public function __construct(Security $security, EntityManagerInterface $entityManager, PeopleRoleService $roles)
    {
        $this->manager  = $entityManager;
        $this->security = $security;
        $this->roles    = $roles;
    }

    public function __invoke(Request $request): JsonResponse
    {
      try {
        
        $company = null;

        if ($this->roles->isSuperAdmin($this->security->getUser()->getPeople()) || $this->roles->isFranchisee($this->security->getUser()->getPeople())) {
          $company = $this->getMyCompany($request->query->get('company'));
        }
        else {
          if ($this->roles->isSalesman($this->security->getUser()->getPeople())) {
            $company = $this->getMyProvider($request->query->get('company'));
          }
        }

        if ($company === null) {
          throw new \Exception('Company was not found');
        }

        // get params

        $professional  = $request->query->get('professional', null);
        $student  = $request->query->get('student', null);
        $from     = $request->query->get('from'  , null);
        $to       = $request->query->get('to'    , null);
        $status   = $request->query->get('status', null);
        $page     = $request->query->get('page'   , 1);
        $limit    = $request->query->get('limit'  , 10);
        $paginate = [
          'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
          'limit' => !is_numeric($limit) ? 10 : $limit
        ];
        $search   = [
          'professional' => $professional,
          'student' => $student,
          'from'    => $from,
          'to'      => $to,
          'status'  => $status,
          'company' => $company->getId(),
        ];

        $repository = $this->manager->getRepository(SchoolClass::class);

        $output = [
          'response' => [
            'data'    => [
              'members' => $repository->getAllProfessionalClasses($search, $paginate),
              'total'   => $repository->getAllProfessionalClasses($search, null, true)
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
      $provider = $this->manager->getRepository(People::class)->find($providerId);
      if ($provider === null)
        return null;

      return $this->manager->getRepository(PeopleSalesman::class)
        ->companyIsMyProvider($this->security->getUser()->getPeople(), $provider) ? $provider : null;
    }
}
