<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\ReceiveInvoice;

class GetSchoolOrderClassesAction
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
    private $security   = null;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->manager  = $entityManager;
        $this->security = $security;
    }

    public function __invoke(ReceiveInvoice $data, Request $request): JsonResponse
    {
      try {

        // get params

        $page     = $request->query->get('page'  , 1);
        $limit    = $request->query->get('limit' , 10);
        $paginate = [
          'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
          'limit' => !is_numeric($limit) ? 10 : $limit
        ];

        $repository = $this->manager->getRepository(ReceiveInvoice::class);

        $output = [
          'response' => [
            'data'    => [
              'members' => $repository->getSchoolOrderClasses($data->getId(), null, $paginate),
              'total'   => $repository->getSchoolOrderClasses($data->getId(), null, null, true)
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
