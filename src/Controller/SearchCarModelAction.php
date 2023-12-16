<?php

namespace App\Controller;

use ControleOnline\Entity\CarModel;
use ControleOnline\Entity\CarYearPrice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchCarModelAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->manager = $entityManager;
    }

    public function __invoke(Request $request): JsonResponse
    {
        /**
         * @var string $search
         */
        $search = $request->get('search', null);

        $search = str_replace(' ', '%', $search);

        $carModels = $this->manager
            ->getConnection()
            ->createQueryBuilder()
            ->select("
                C.id AS modelId,
                CONCAT(CM.value, '-', C.value, '-', CY.value) AS value,
                CY.price,
                CY.id AS priceId,
                CONCAT(CM.label, ' ', C.label, ' ', CY.label) AS label
            ")
            ->from('car_model', 'C')
            ->leftJoin("C", "car_year_price", 'CY', "CY.car_model_id = C.id")
            ->leftJoin("C", "car_manufacturer", 'CM', "CM.id = C.car_manufacturer_id")
            ->where('CONCAT(CM.label, " ", C.label, " ", CY.label) LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults(20)
            ->orderBy('label', 'ASC')
            ->execute()
            ->fetchAll();

        return new JsonResponse([
            'response' => [
                'data'    => $carModels,
                'count'   => count($carModels),
                'error'   => '',
                'success' => true,
            ],
        ]);
    }
}
