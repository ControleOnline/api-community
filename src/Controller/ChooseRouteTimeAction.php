<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\SalesOrder as Order;
use App\Service\AddressService;
use App\Service\PeopleService;

class ChooseRouteTimeAction
{

    public function __construct(
        EntityManagerInterface $entityManager,
        AddressService $address,
        PeopleService $people
    ) {
        $this->manager = $entityManager;
        $this->address = $address;
        $this->people  = $people;
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {
            if ($content = $request->getContent()) {
                $params = json_decode($content, true);

                $this->manager->getConnection()->beginTransaction();

                $data->addOtherInformations('route_time', $params['route_time']);                
                $data->setQuote($data->getQuotes()->first());                
                $quote = $data->getQuote();
                $quote->setDeadline($params['route_time']);

                $this->manager->persist($quote);
                $this->manager->persist($data);

                $this->manager->flush();
                $this->manager->getConnection()->commit();
            }

            return new JsonResponse(['@id' => $data->getId()], 200);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }

            return new JsonResponse([
                'response' => [
                    'data'    => null,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
}
