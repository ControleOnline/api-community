<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\PurchasingOrder as Order;
use App\Entity\Address;
use App\Entity\Email;
use App\Entity\People;
use App\Entity\Phone;
use App\Entity\Language;
use ControleOnline\Entity\Status;
use App\Entity\Document;
use App\Entity\DocumentType;
use ControleOnline\Entity\PurchasingOrder;
use App\Entity\SalesOrder;
use App\Service\AddressService;
use App\Service\PeopleService;

class ChooseQuoteAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    protected $manager = null;

    /**
     * Address Service
     *
     * @var \App\Service\AddressService
     */
    protected $address = null;

    /**
     * People Service
     *
     * @var \App\Service\PeopleService
     */
    protected $people = null;

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

    public function updateOrder(Order $order, array $params)
    {
        // get dependencies

        $payerDocument = isset($params['payerDocument']) ? $params['payerDocument'] : null;

        /**
         * @var \Doctrine\Common\Collections\ArrayCollection $quotes
         */
        $quotes = $order->getQuotes();
        $quote  = $quotes->matching(Criteria::create()->andWhere(Criteria::expr()->eq('id', $params['quote'])))->first();
        if ($quote === false)
            throw new ItemNotFoundException('Quote order not found');

        $sender = $this->manager->getRepository(People::class)->find($params['retrieve']['id']);
        if ($sender === null)
            throw new ItemNotFoundException('Sender not found');

        $receiver = $this->manager->getRepository(People::class)->find($params['delivery']['id']);
        if ($receiver === null)
            throw new ItemNotFoundException('Receiver not found');

        $rcontact = $this->manager->getRepository(People::class)->find($params['retrieve']['contact']);
        if ($rcontact === null)
            throw new ItemNotFoundException('Retrieve contact not found');

        $dcontact = $this->manager->getRepository(People::class)->find($params['delivery']['contact']);
        if ($dcontact === null)
            throw new ItemNotFoundException('Delivery contact not found');

        $payer = $this->getPeopleFromContact($params['payer']);
        if ($payer === null)
            throw new InvalidValueException('Payer not found');

        $oAddress = $this->getAddressEntity($params['retrieve']['address'], $sender);
        if ($oAddress === null)
            throw new InvalidValueException('Origin address not found');

        if ($oAddress->getStreet()->getDistrict()->getCity()->getCity() != $quote->getCityOrigin()->getCity())
            throw new InvalidValueException('Origin city can not be different from order origin city');

        if ($oAddress->getStreet()->getDistrict()->getCity()->getState()->getUF() != $quote->getCityOrigin()->getState()->getUf())
            throw new InvalidValueException('Origin state can not be different from order origin state');

        $dAddress = $this->getAddressEntity($params['delivery']['address'], $receiver);
        if ($dAddress === null)
            throw new InvalidValueException('Destination address not found');

        if ($dAddress->getStreet()->getDistrict()->getCity()->getCity() != $quote->getCityDestination()->getCity())
            throw new InvalidValueException('Destination city can not be different from order destination city');

        if ($dAddress->getStreet()->getDistrict()->getCity()->getState()->getUF() != $quote->getCityDestination()->getState()->getUf())
            throw new InvalidValueException('Destination state can not be different from order destination state');

        if (!empty($payerDocument)) {
            $document = $this->manager->getRepository(Document::class)
                ->findOneBy(['document' => $payerDocument]);

            if (!empty($document) || $document instanceof Document) {
                throw new InvalidValueException('Payer document is already registered!');
            }

            $document = $payer->getDocument();

            if (!empty($document) && count($document) > 0) {
                throw new InvalidValueException('Payer already has a document!');
            }

            $documentType = $this->people->getPeopleDocumentTypeByDoc($payerDocument);

            $data = [
                "documents" => [
                    ['document' => $payerDocument, 'type' => $documentType === "CPF" ? 2 : 3]
                ]
            ];

            $this->people->createDocument($data, $payer);
        }

        // update order
        $order->setQuote($quote);
        $order->setPayer($payer);
        $order->setAddressOrigin($oAddress);
        $order->setAddressDestination($dAddress);
        $order->setRetrievePeople($sender);
        $order->setDeliveryPeople($receiver);
        $order->setRetrieveContact($rcontact);
        $order->setDeliveryContact($dcontact);
        $order->setStatus($this->getStatus());
        $order->setComments(empty($params['comments']) ? null : $params['comments']);
        $order->setPrice($quote->getTotal());

        $this->manager->persist($order);
    }

    protected function getAddressEntity($address, People $people): ?Address
    {
        // if $address is an id

        if (!is_array($address))
            return $this->manager->getRepository(Address::class)->find($address);

        return $this->address->createFor($people, $address);
    }

    protected function getPeopleFromContact($contact): ?People
    {
        if (is_numeric($contact))
            return $this->manager->getRepository(People::class)->find($contact);

        else {
            if (is_array($contact)) {

                // using document, verify if people exists

                if (!isset($contact['document']))
                    throw new InvalidValueException('Document is not defined');

                $people = $this->getPeopleByDocument($contact['document']);

                if ($people instanceof People) {

                    // verify if email exists

                    $email = $this->manager->getRepository(Email::class)->findOneBy(['email' => $contact['email']]);

                    if ($email === null) {
                        $email = new Email();
                        $email->setPeople($people);
                        $email->setEmail($contact['email']);

                        $this->manager->persist($email);
                    }

                    // verify if contact phone already exists

                    $pcode  = substr($contact['phone'], 0, 2);
                    $number = substr($contact['phone'], 2);
                    $phone  = $this->manager->getRepository(Phone::class)->findOneBy(['ddd' => $pcode, 'phone' => $number, 'people' => $people]);

                    if ($phone === null) {
                        $phone = new Phone();
                        $phone->setPeople($people);
                        $phone->setDdd($pcode);
                        $phone->setPhone($number);

                        $this->manager->persist($phone);
                    }
                } else {

                    $people = new People();
                    $people->setName($contact['name']);
                    $people->setPeopleType($this->getDocumentType($contact['document']) == 'CNPJ' ? 'J' : 'F');
                    $people->setLanguage($this->getDefaultLanguage());
                    $people->setAlias($contact['alias']);
                    $people->setEnabled(true);

                    $this->manager->persist($people);

                    $document = new Document();
                    $document->setDocument($contact['document']);
                    $document->setDocumentType($this->getPeopleDocumentType($contact['document']));
                    $document->setPeople($people);

                    $this->manager->persist($document);

                    $phone = new Phone();
                    $phone->setPeople($people);
                    $phone->setDdd(substr($contact['phone'], 0, 2));
                    $phone->setPhone(substr($contact['phone'], 2));

                    $this->manager->persist($phone);

                    $email = new Email();
                    $email->setPeople($people);
                    $email->setEmail($contact['email']);

                    $this->manager->persist($email);
                }

                // save contact address

                $this->address->createFor($people, $contact['address']);

                return $people;
            }
        }
    }

    public function getPeopleByDocument(string $document): ?People
    {
        $docType  = $this->getPeopleDocumentType($document);
        $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $document, 'documentType' => $docType]);

        return $document instanceof Document ? $document->getPeople() : null;
    }

    protected function getPeopleDocumentType(string $document): ?DocumentType
    {
        $filter = [
            'documentType' => $this->getDocumentType($document),
            'peopleType'   => $this->getDocumentType($document) == 'CNPJ' ? 'J' : 'F',
        ];

        return $this->manager->getRepository(DocumentType::class)->findOneBy($filter);
    }

    protected function getDocumentType(string $document): ?string
    {
        return strlen($document) == 14 ? 'CNPJ' : (strlen($document) == 11 ? 'CPF' : null);
    }

    protected function getDefaultLanguage(): ?Language
    {
        return $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
    }

    protected function getStatus(PurchasingOrder $order = null): ?Status
    {
        if ($order && $order->getContract() != null) {
            return $this->manager->getRepository(Status::class)->findOneBy(['status' => 'automatic analysis']);
        } else {
            return $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting client invoice tax']);
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

        // who sends

        if (!isset($params['retrieve']))
            throw new InvalidValueException('Param "retrieve" is missing');
        else {

            // must define retrieve id

            if (!isset($params['retrieve']['id']))
                throw new InvalidValueException('Param "retrieve id" is missing');

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

        // who receives

        if (!isset($params['delivery']))
            throw new InvalidValueException('Param "delivery" is missing');
        else {

            // must define delivery id

            if (!isset($params['delivery']['id']))
                throw new InvalidValueException('Param "delivery id" is missing');

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

    protected function isFullContact(array $contact): bool
    {
        if (!isset($contact['email']))
            throw new InvalidValueException('Param "contact email" is missing');
        else if (!filter_var($contact['email'], FILTER_VALIDATE_EMAIL))
            throw new InvalidValueException('Param "contact email" is not valid');

        if (!isset($contact['name']))
            throw new InvalidValueException('Param "contact name" is missing');

        if (!isset($contact['phone']))
            throw new InvalidValueException('Param "contact phone" is missing');
        else if (preg_match('/^[0-9]{6,11}$/', $contact['phone']) !== 1)
            throw new InvalidValueException('Param "contact phone" is not valid');

        return true;
    }
}
