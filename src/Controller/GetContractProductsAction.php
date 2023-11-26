<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use App\Entity\People;
use App\Entity\MyContract;
use App\Entity\MyContractProduct;

class GetContractProductsAction
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $manager  = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(MyContract $data, Request $request): JsonResponse
    {
      $page     = $request->query->get('page'  , 1);
      $limit    = $request->query->get('limit' , 10);
      $paginate = [
        'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
        'limit' => !is_numeric($limit) ? 10 : $limit
      ];

      $products = $this->manager->getRepository(MyContractProduct::class)
        ->getContractProducts($data->getId(), null, $paginate);

      return new JsonResponse([
        'members' => $products,
        'total'   => $this->manager->getRepository(MyContractProduct::class)
          ->getContractProducts($data->getId(), null, null, true),
      ]);
    }
}
