<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\PurchasingOrder as Order;
use App\Entity\People;
use App\Entity\Document;
use App\Entity\Language;

use App\Service\AddressService;
use App\Service\PeopleService;
use App\Controller\ChooseQuoteAction;

class ChooseQuoteRetrieveAction extends ChooseQuoteAction
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
                'retrieve_people' => $data->getRetrievePeople()->getId()
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

        // who sends

        if (!isset($params['retrieve']))
            throw new InvalidValueException('Param "retrieve" is missing');
        else {

            // must define retrieve id

            //if (!isset($params['retrieve']['id']))
            //throw new InvalidValueException('Param "retrieve id" is missing');

            // must define address

            if (!isset($params['retrieve']['address']))
                throw new InvalidValueException('Param "retrieve address" is missing');
            else {
                if (is_array($params['retrieve']['address']))
                    $this->address->isFullAddress($params['retrieve']['address']);
            }

            // must define contact

            if (!isset($params['retrieve']['contact'])) {
                throw new InvalidValueException('Param "retrieve contact" is missing');
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


        if ($params['myCompany'] && $params['retrieve']['whereRetrieve'] == 'MC' && $params['retrieve']['personType'] == 'PJ') {
            $sender = $this->manager->getRepository(People::class)->find($params['myCompany']);
        } elseif ($document) {
            $sender = $this->manager->getRepository(People::class)->findOneBy([
                'document' => $this->em->getRepository(Document::class)->findOneBy([
                    'document' => preg_replace('/[^0-9]/', '',  $params['document'])
                ])
            ]);
        } elseif ($params['retrieve']['personType'] == 'PJ') {

            $lang = $this->manager->getRepository(Language::class)
                ->findOneBy([
                    'language' => 'pt-BR'
                ]);

            $sender = new People();
            $sender->setEnabled(1);
            $sender->setLanguage($lang);
            $sender->setPeopleType('J');
            $sender->setName($params['retrieve']['name']);
            $sender->setAlias($params['retrieve']['alias']);
            $this->manager->persist($sender);
            $this->manager->flush();
        } else {
            $sender = $this->manager->getRepository(People::class)->find($params['retrieve']['id'] ?: $params['retrieve']['contact']);
        }
        if ($sender === null)
            throw new ItemNotFoundException('Sender not found');


        $rcontact = $this->manager->getRepository(People::class)->find($params['retrieve']['contact']);
        if ($rcontact === null)
            throw new ItemNotFoundException('Retrieve contact not found');



        $oAddress = $this->getAddressEntity($params['retrieve']['address'], $sender);
        if ($oAddress === null)
            throw new InvalidValueException('Origin address not found');

        if ($oAddress->getStreet()->getDistrict()->getCity()->getCity() != $quote->getCityOrigin()->getCity())
            throw new InvalidValueException('Origin city can not be different from order origin city');

        if ($oAddress->getStreet()->getDistrict()->getCity()->getState()->getUF() != $quote->getCityOrigin()->getState()->getUf())
            throw new InvalidValueException('Origin state can not be different from order origin state');


        // update order
        $order->setQuote($quote);
        $order->setAddressOrigin($oAddress);
        $order->setRetrievePeople($sender);
        $order->setRetrieveContact($rcontact);
        $order->setPrice($quote->getTotal());

        $this->manager->persist($order);
    }
}
