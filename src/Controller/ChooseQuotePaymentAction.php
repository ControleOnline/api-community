<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Order as Order;
use ControleOnline\Entity\Document;


use App\Service\AddressService;
use App\Service\PeopleService;

use App\Controller\ChooseQuoteAction;

class ChooseQuotePaymentAction extends ChooseQuoteAction
{

    public function __construct(
        EntityManagerInterface $entityManager,
        PeopleService $people
    ) {
        $this->manager = $entityManager;

        $this->people  = $people;
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {
            if ($content = $request->getContent()) {
                $params = json_decode($content, true);

                if (!$data->justOpened())
                    throw new InvalidValueException('This order was already updated');

                if ($this->paramsAreValid($params)) {
                    $this->manager->getConnection()->beginTransaction();

                    $this->updateOrder($data, $params);

                    $this->manager->flush();
                    $this->manager->getConnection()->commit();
                }
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


    protected function paramsAreValid(array $params): bool
    {
        if (empty($params))
            throw new InvalidValueException('Params can not be empty');

        if (!isset($params['paymentType']))
            throw new InvalidValueException('Param "paymentType" is missing');

        return true;
    }

    public function updateOrder(Order $order, array $params)
    {
        $order->addOtherInformations('paymentType', $params['paymentType']);
        $order->setStatus($this->getStatus($order));
        if ($order->getPayer() == null) {
            $order->setPayer($order->getClient());
        }
        $this->manager->persist($order);
    }
}
