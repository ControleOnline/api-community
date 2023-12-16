<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleCarrier;

class ChangeCarrierStatusAction
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
        $id = $request->get('id', null);
        $newStatus = $request->get('new_status', null);
        $newStatus = intval($newStatus);

        /**
         * @var PeopleRepository
         */
        $peopleRepository = $this->manager->getRepository(PeopleCarrier::class);



        /**
         * Entity People
         * 
         * @var People
         */
        $client = $peopleRepository->findOneBy(["id" => $id]);


        if ($client != null) {
            $client->setEnabled($newStatus);

            $this->manager->getConnection()->beginTransaction();
            $this->manager->persist($client);
            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => $newStatus,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);
        } else {
            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 1,
                    'error'   => 'Customer not found',
                    'success' => false,
                ],
            ], 200);
        }
    }
}
