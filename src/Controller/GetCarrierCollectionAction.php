<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Carrier;
use App\Entity\People;
use App\Repository\CarrierRepository;

class GetCarrierCollectionAction
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
     * Carrier repository
     *
     * @var CarrierRepository
     */
    private $carriers = null;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->manager  = $entityManager;
        $this->security = $security;
        $this->carriers = $this->manager->getRepository(Carrier::class);
    }

    public function __invoke(Request $request): JsonResponse
    {
      try {

        // get params

        $search   = $request->query->get('searchBy', null);
        $page     = $request->query->get('page'  , 1);
        $limit    = $request->query->get('limit' , 10);
        $paginate = [
          'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
          'limit' => !is_numeric($limit) ? 10 : $limit
        ];
        $search   = [
          'search' => $search,
        ];

        $company = $this->getMyCompany($request->query->get('myProvider', null));
        if ($company instanceof People) {
          $search['company'] = $company;
        }

        $output = [
          'response' => [
            'data'    => [
              'members' => $company !== null ? $this->carriers->getAllCarriers($search, $paginate ) : [],
              'total'   => $company !== null ? $this->carriers->getAllCarriers($search, null, true) : 0
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

    private function getMyCompany($companyId): ?People
    {
      if (empty($companyId)) {
        $companies = $this->security->getUser()->getPeople() ?
          $this->security->getUser()->getPeople()->getPeopleCompany() : null;

        if (empty($companies) || $companies->first() === false)
          return null;

        return $companies->first()->getCompany();
      }

      $company = $this->manager->find(People::class, $companyId);

      if ($company instanceof People) {

        $isMyCompany = $this->security->getUser()->getPeople()->getPeopleCompany()->exists(
          function ($key, $element) use ($company) {
            return $element->getCompany() === $company;
          }
        );

        if ($isMyCompany === true) {
          return $company;
        }
      }

      return null;
    }
}
