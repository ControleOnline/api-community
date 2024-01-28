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
use ControleOnline\Entity\People;

class ChooseQuotePayerAction extends ChooseQuoteAction
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

        // selected quotation

        if (!isset($params['quote']))
            throw new InvalidValueException('Param "quote" is missing');

        // who is the payer

        if (!isset($params['payer']))
            throw new InvalidValueException('Param "payer" is missing');
        else {
            if (!is_numeric($params['payer'])) {
                if (is_array($params['payer'])) {
                    $this->isFullContact($params['payer']);
                    $this->address->isFullAddress($params['payer']['address']);
                } else
                    throw new InvalidValueException('Param "payer" is not valid');
            }
        }

        return true;
    }

    public function updateOrder(Order $order, array $params)
    {
        // get dependencies

        $payerDocument = isset($params['payerDocument']) ? $params['payerDocument'] : null;
        $payerId = isset($params['payer']) ? $params['payer'] : null;

        /**
         * @var \Doctrine\Common\Collections\ArrayCollection $quotes
         */
        $quotes = $order->getQuotes();
        $quote  = $quotes->matching(Criteria::create()->andWhere(Criteria::expr()->eq('id', $params['quote'])))->first();
        if ($quote === false)
            throw new ItemNotFoundException('Quote order not found');


        $payer = $this->manager->getRepository(People::class)->find($payerId);

        if ($payer === false)
            throw new ItemNotFoundException('Payer not found');


        $document = $payer->getOneDocument();

        if (!empty($payerDocument) && $document == null) {
            $document = $this->manager->getRepository(Document::class)
                ->findOneBy(['document' => $payerDocument]);

            if (empty($document)) {
                $documentType = $this->people->getPeopleDocumentTypeByDoc($payerDocument);

                $data = [
                    "documents" => [
                        ['document' => $payerDocument, 'type' => $documentType === "CNPJ" ? 3 : 2]
                    ]
                ];
                $document = $this->people->createDocument($data, $payer);
            } else {
                throw new InvalidValueException('Document is already in use');
            }
        } elseif ($document == null) {
            throw new InvalidValueException('Need a Document number');
        }

        // update order
        $order->setQuote($quote);
        $order->setPayer($payer);
        $order->setStatus($this->getStatus($order));
        $order->setComments(empty($params['comments']) ? null : $params['comments']);
        $order->setPrice($quote->getTotal());

        $this->manager->persist($order);
    }
}
