<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\Order as Order;
use ControleOnline\Entity\People;

class UpdateOrderRemoteAction
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

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            
            $newProviderId = $payload['providerId'];

            if (!isset($newProviderId)) {
                throw new \InvalidArgumentException('New order provider was not defined', 400);
            }

            $peopleRepo = $this->manager->getRepository(People::class);

            $provider = $peopleRepo->findOneBy(array('id' => $newProviderId));

            if (!empty($provider)) {
                $data->setProvider($provider);

                $this->manager->persist($data);
                $this->manager->flush();

                return new JsonResponse([
                    'response' => [
                    'data'    => array(
                        "newProviderId" => $newProviderId
                    ),
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                    ],
                ]);
            }
            else {
                throw new \InvalidArgumentException('New order provider was not found', 400);
            }

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

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
}