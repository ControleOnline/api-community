<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\PurchasingOrder as Order;
use App\Entity\People;
use App\Entity\Document;
use App\Entity\Language;
use App\Service\AddressService;
use App\Service\PeopleService;

use App\Controller\ChooseQuoteAction;

class ChooseQuoteDeliveryAction extends ChooseQuoteAction
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
                $params['myCompany'] = $request->get('myCompany', null);

                if (!$data->justOpened())
                    throw new InvalidValueException('This order was already updated');

                if ($this->paramsAreValid($params)) {
                    $this->manager->getConnection()->beginTransaction();

                    $this->updateOrder($data, $params);

                    $this->manager->flush();
                    $this->manager->getConnection()->commit();
                }
            }

            return new JsonResponse([
                '@id' => $data->getId(),
                'delivery_people' => $data->getDeliveryPeople()->getId()
            ], 200);
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

        // who receives

        if (!isset($params['delivery']))
            throw new InvalidValueException('Param "delivery" is missing');
        else {

            // must define delivery id

            //if (!isset($params['delivery']['id']))
            //throw new InvalidValueException('Param "delivery id" is missing');

            // must define address

            if (!isset($params['delivery']['address']))
                throw new InvalidValueException('Param "delivery address" is missing');
            else {
                if (is_array($params['delivery']['address']))
                    $this->address->isFullAddress($params['delivery']['address']);
            }

            // must define contact

            if (!isset($params['delivery']['contact'])) {
                throw new InvalidValueException('Param "delivery contact" is missing');
            }
        }

        return true;
    }
    public function updateOrder(Order $order, array $params)
    {

        // get dependencies
        $document = isset($params['document']) ? $params['document'] : null;

        /**
         * @var \Doctrine\Common\Collections\ArrayCollection $quotes
         */
        $quotes = $order->getQuotes();
        $quote  = $quotes->matching(Criteria::create()->andWhere(Criteria::expr()->eq('id', $params['quote'])))->first();
        if ($quote === false)
            throw new ItemNotFoundException('Quote order not found');


        if ($params['myCompany'] && $params['delivery']['whereDelivery'] == 'MC' && $params['delivery']['personType'] == 'PJ') {
            $receiver = $this->manager->getRepository(People::class)->find($params['myCompany']);
        } elseif ($document) {
            $receiver = $this->manager->getRepository(People::class)->findOneBy([
                'document' => $this->em->getRepository(Document::class)->findOneBy([
                    'document' => preg_replace('/[^0-9]/', '',  $params['document'])
                ])
            ]);
        } elseif ($params['delivery']['personType'] == 'PJ') {

            $lang = $this->manager->getRepository(Language::class)
                ->findOneBy([
                    'language' => 'pt-BR'
                ]);

            $receiver = new People();
            $receiver->setEnabled(1);
            $receiver->setLanguage($lang);
            $receiver->setPeopleType('J');
            $receiver->setName($params['delivery']['name']);
            $receiver->setAlias($params['delivery']['alias']);
            $this->manager->persist($receiver);
            $this->manager->flush();
        } else {
            $receiver = $this->manager->getRepository(People::class)->find($params['delivery']['id'] ?: $params['delivery']['contact']);
        }
        if ($receiver === null)
            throw new ItemNotFoundException('Receiver not found');


        $dcontact = $this->manager->getRepository(People::class)->find($params['delivery']['contact']);
        if ($dcontact === null)
            throw new ItemNotFoundException('Delivery contact not found');

        $dAddress = $this->getAddressEntity($params['delivery']['address'], $receiver);
        if ($dAddress === null)
            throw new InvalidValueException('Destination address not found');

        if ($dAddress->getStreet()->getDistrict()->getCity()->getCity() != $quote->getCityDestination()->getCity())
            throw new InvalidValueException('Destination city can not be different from order destination city');

        if ($dAddress->getStreet()->getDistrict()->getCity()->getState()->getUF() != $quote->getCityDestination()->getState()->getUf())
            throw new InvalidValueException('Destination state can not be different from order destination state');

        // update order
        $order->setQuote($quote);

        $order->setAddressDestination($dAddress);
        $order->setDeliveryPeople($receiver);
        $order->setDeliveryContact($dcontact);
        $order->setPrice($quote->getTotal());

        $this->manager->persist($order);
    }
}
