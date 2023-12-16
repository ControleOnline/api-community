<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\People;

class GetCloseProfessionalsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Professional repository
     *
     * @var PeopleRepository
     */
    private $professionals = null;
    
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->manager  = $entityManager;
        $this->professionals = $this->manager->getRepository(People::class);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $results = array();
        
        $page  = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        
        if (!($lat = $request->get('lat', null)))
        throw new BadRequestHttpException('lat is required');
        
        if (!($lng = $request->get('lng', null)))
        throw new BadRequestHttpException('lng is required');

        $lat = (double) str_replace(',', '.', $lat);
        $lng = (double) str_replace(',', '.', $lng);

        $repo = $this->manager->getConnection()->createQueryBuilder();

        $adresses = $repo->select(array(
                'a.id as address_id',
                'p.id as people_id',
                '('.
                    '('.
                        '('.
                            'acos('.
                                'sin(( ' . $lat . ' * pi() / 180))'.
                                '*'.
                                'sin(( a.latitude * pi() / 180)) + cos(( ' . $lat . ' * pi() /180 ))'.
                                '*'.
                                'cos(( a.latitude * pi() / 180)) * cos((( ' . $lng . ' - a.longitude) * pi()/180)))'.
                        ') * 180/pi()'.
                    ') * 60 * 1.1515 * 1.609344'.
                ') as distance'
            ))
            ->from('address', 'a')
            ->innerJoin('a', 'people', 'p', 'a.people_id = p.id')
            ->innerJoin('a', 'people_professional', 'pt', 'pt.professional_id = p.id')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('distance', 'ASC')
            ->execute()
            ->fetchAll();

        if (!empty($adresses)) {
            $ids = array();

            foreach($adresses as $result) {
                $ids[] = $result['people_id'];
            }

            $results = $this->professionals->getAllProfessionals(array('ids' => $ids));

            foreach($results as $key => $result) {
                $distance = null;

                foreach($adresses as $ad) {
                    if ($ad['people_id'] == $result['id']) {
                        $distance = $ad['distance'];
                    }
                }

                if (!empty($distance)) {
                    $results[$key]['distance'] = (double) $distance;
                }
            }
        }

        return new JsonResponse([
            'response' => [
                'data'    => $results,
                'success' => true,
            ]
        ], 200);
    }

    public function error(?string $message, ?\Exception $e = null) {
        
        if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

        return new JsonResponse([
            'response' => [
                'data'    => [],
                'count'   => 0,
                'error'   => !empty($message) ? $message : $e->getMessage(),
                'success' => false,
            ],
        ]);

    }
}