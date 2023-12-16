<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

use ControleOnline\Entity\SalesOrder as Order;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\State;
use ControleOnline\Entity\City;
use ControleOnline\Entity\District;
use ControleOnline\Entity\Street;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\OrderPackage;
use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\QuoteDetail;
use ControleOnline\Entity\DeliveryTax;
use ControleOnline\Entity\User;
use ControleOnline\Entity\PeopleStates;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Quote;
use App\Library\Utils\Address as AddressComponents;
use App\Service\AddressService;

class QuoteRepository
{
  private $params     = [];

  private $quotations = [];

  private $memo       = null;

  private $em         = null;

  private $user       = null;

  private $address    = null;

  public function __construct(EntityManagerInterface $manager, Security $security, RequestStack $request, AddressService $address)
  {
    $this->em      = $manager;
    $this->memo    = new \App\Library\Utils\Memory();
    $this->user    = $security->getUser();
    $this->reqs    = $request->getCurrentRequest();
    $this->address = $address;
  }

  public function setParams(array $params)
  {
    $this->params = $params;
  }

  public function setQuotations(array $quotations)
  {
    $this->quotations = $quotations;
  }

  public function persist(): array
  {
    try {
      $this->em->getConnection()->beginTransaction();

      $this->doPersist();

      $this->em->flush();
      $this->em->getConnection()->commit();

      $this->setQuotationKeys();

      return [
        'orderId'    => $this->memo->order->getId(),
        'quotations' => $this->quotations,
        'status'     => true,
        'message'    => 'OK',
      ];
    } catch (\Exception $e) {
      $this->em->getConnection()->rollBack();

      return [
        'orderId'    => null,
        'quotations' => [],
        'status'     => false,
        'message'    => $e->getMessage(),
      ];
    }
  }

  private function doPersist()
  {
    $this->memo->add('ocity', $this->getCity($this->params['addressComponents']['origin']));
    if (!$this->memo->ocity     instanceof City)
      throw new \Exception('Can not persist without an origin city');

    $this->memo->add('dcity', $this->getCity($this->params['addressComponents']['destination']));
    if (!$this->memo->dcity     instanceof City)
      throw new \Exception('Can not persist without a destination city');

    $this->memo->add('provider', $this->getProvider());
    if (!$this->memo->provider instanceof People)
      throw new \Exception('Can not persist without a provider');

    $this->memo->add('client', $this->getClient());
    if (!$this->memo->client   instanceof People)
      throw new \Exception('Can not persist without a client');

    $this->memo->add('retrieve_contact', $this->getContact());

    // persist order
    $this->memo->add('order', $this->createOrder());

    // persist packages
    if ($this->params['hasPackages'])
      $this->createOrderPackages();

    // persist quotations
    $this->createQuotations();

    // persist pickup and delivery (optional)
    $this->setOrderPickupAndDelivery();
  }

  private function setOrderPickupAndDelivery()
  {
    if (!$this->memo->order instanceof Order) {
      return;
    }

    if (isset($this->params['pickup'])) {
      $pickup = $this->getPeopleTrackEntities($this->params['pickup']);

      $this->memo->order->setRetrievePeople($pickup['people']);
      $this->memo->order->setAddressOrigin($pickup['address']);
      $this->memo->order->setRetrieveContact($pickup['contact']);
    }

    if (isset($this->params['delivery'])) {
      $delivery = $this->getPeopleTrackEntities($this->params['delivery']);

      $this->memo->order->setDeliveryPeople($delivery['people']);
      $this->memo->order->setAddressDestination($delivery['address']);
      $this->memo->order->setDeliveryContact($delivery['contact']);
    }
  }

  private function getPeopleTrackEntities(array $params): array
  {
    // get people

    if (is_numeric($params['people'])) {
      $people = $this->em->getRepository(People::class)->find($params['people']);
      if ($people === null) {
        throw new \Exception('People id not found');
      }
    } else {
      if (filter_var($params['people'], FILTER_VALIDATE_EMAIL)) {
        $email = $this->em->getRepository(Email::class)->findOneBy(['email' => $params['people']]);
        if ($email === null) {
          throw new \Exception('People email not found');
        } else {
          $people = $email->getPeople();
          if ($people === null) {
            throw new \Exception('People not found');
          }
        }
      }
    }

    // get address

    if (is_array($params['address'])) {
      $address = $this->address->createFor($people, $params['address']);
    }

    // get contact

    if (is_numeric($params['contact'])) {
      $contact = $this->em->getRepository(People::class)->find($params['contact']);
      if ($contact === null) {
        throw new \Exception('Contact id not found');
      }
    } else {
      if (filter_var($params['contact'], FILTER_VALIDATE_EMAIL)) {
        $email = $this->em->getRepository(Email::class)->findOneBy(['email' => $params['contact']]);
        if ($email === null) {
          throw new \Exception('Contact email not found');
        } else {
          $contact = $email->getPeople();
          if ($contact === null) {
            throw new \Exception('Contact not found');
          }
        }
      }
    }

    return [
      'people'  => $people,
      'address' => $address,
      'contact' => $contact,
    ];
  }

  private function setQuotationKeys()
  {
    foreach ($this->quotations as $index => $quotation) {
      $this->quotations[$index]['id'] = $this->quotations[$index]['__entity']->getId();

      unset($this->quotations[$index]['__entity']);
    }
  }

  private function createOrder(): Order
  {
    $orStatus = $this->em->getRepository(Status::class)->findOneBy(['status' => 'quote']);
    if ($orStatus === null)
      throw new \Exception('Order status not found');

    $oAddress = $this->getAddress($this->params['addressComponents']['origin']);
    $dAddress = $this->getAddress($this->params['addressComponents']['destination']);

    $order = new Order();

    $order->setClient($this->memo->client);
    $order->setProvider($this->memo->provider);
    $order->setRetrieveContact($this->memo->retrieve_contact);
    $order->setRetrievePeople($this->memo->client);
    $order->setCubage($this->params['finalWeight']);
    $order->setProductType($this->params['productType']);
    $order->setInvoiceTotal($this->params['productTotalPrice']);
    $order->setPrice($this->quotations[0]['total']);
    $order->setApp($this->params['app']);
    $order->setStatus($orStatus);
    $order->setAddressOrigin($oAddress);
    $order->setAddressDestination($dAddress);
    $order->setNotified(false);

    $this->em->persist($order);

    return $order;
  }

  private function createOrderPackages()
  {
    foreach ($this->params['packages'] as $package) {
      $orderPackage = new OrderPackage();

      $orderPackage->setOrder($this->memo->order);
      $orderPackage->setQtd($package['qtd']);
      $orderPackage->setHeight($package['height']);
      $orderPackage->setWeight($package['weight']);
      $orderPackage->setWidth($package['width']);
      $orderPackage->setDepth($package['depth']);

      $this->em->persist($orderPackage);
    }
  }

  private function createQuotations()
  {
    foreach ($this->quotations as $index => $quote) {
      $quotation = new Quotation();

      $quotation->setOrder($this->memo->order);
      $quotation->setClient($this->memo->client);
      $quotation->setProvider($this->memo->provider);
      $quotation->setCarrier($this->em->getRepository(People::class)->find($quote['carrier']['id']));
      $quotation->setCityOrigin($this->memo->ocity);
      $quotation->setCityDestination($this->memo->dcity);
      $quotation->setDeadline($quote['deliveryDeadline'] ?: 0);
      $quotation->setDenied(false);
      $quotation->setTotal($quote['total']);
      $quotation->setIp(null);
      $quotation->setInternalIp(null);

      $this->em->persist(
        $this->quotations[$index]['__entity'] = $quotation
      );

      if (empty($quote['taxes']))
        throw new \Exception('Quotation taxes is empty');

      $this->createQuotationDetails($quotation, $quote['taxes']);
    }
  }

  private function createQuotationDetails(Quotation $quotation, array $taxes)
  {
    foreach ($taxes as $tax) {

      $quoteDetail = new QuoteDetail();


      /*
      * @todo melhorar para que não seja necessária esta consulta
      */
      if (!isset($tax['region_origin_id']) || !isset($tax['region_destination_id'])) {
        $deliveryTax = $this->em->find(DeliveryTax::class, $tax['id']);
        if ($deliveryTax !== null) {
          $tax['region_origin_id'] = $deliveryTax->getRegionOrigin();
          $tax['region_destination_id'] = $deliveryTax->getRegionDestination();
        }
      }      


      $quoteDetail->setQuote($quotation);
      $quoteDetail->setDeliveryTax($deliveryTax);
      $quoteDetail->setTaxName($tax['name']);
      $quoteDetail->setTaxDescription($tax['description']);
      $quoteDetail->setTaxType($tax['type']);
      $quoteDetail->setTaxSubtype($tax['subType']);
      $quoteDetail->setMinimumPrice($tax['minimumPrice']);
      $quoteDetail->setFinalWeight($tax['weight']);
      $quoteDetail->setRegionOrigin(isset($tax['region_origin_id']) ? $tax['region_origin_id'] : null);
      $quoteDetail->setRegionDestination(isset($tax['region_destination_id']) ? $tax['region_destination_id'] : null);
      $quoteDetail->setTaxOrder(0);
      $quoteDetail->setPrice($tax['price']);
      $quoteDetail->setOptional(0);
      $quoteDetail->setPriceCalculated($tax['subtotal']);

      $this->em->persist($quoteDetail);
    }
  }


  private function getContact(): ?People
  {
    if ($this->params['contactData'] !== false) {
      $data  = $this->params['contactData'];
      $email = $this->em->getRepository(Email::class)->findOneBy(['email' => $data['email']]);

      if ($email === null)
        return $this->createClient($data);

      if (($people = $email->getPeople()) === null)
        throw new \Exception(
          sprintf('People client with email "%s" was not found', $data['email'])
        );

      return $people;
    }

    return null;
  }


  private function getClient(): ?People
  {
    if (!empty($this->params['selectedCompany']))
      return $this->em->find(People::class, $this->params['selectedCompany']);

    if ($this->params['contactData'] !== false) {
      $data  = $this->params['contactData'];
      $email = $this->em->getRepository(Email::class)->findOneBy(['email' => $data['email']]);

      if ($email === null)
        return $this->createClient($data);

      if (($people = $email->getPeople()) === null)
        throw new \Exception(
          sprintf('People client with email "%s" was not found', $data['email'])
        );

      if ($people->getPeopleCompany()->count() == 0)
        return $people;

      return $people->getPeopleCompany()->first()->getCompany();
    }
    // if user is logged in
    else if ($this->user instanceof User) {

      // if order client is one of my companies (query param myCompany defined)

      $company = $this->getPeopleClient();

      if ($company instanceof People)
        return $company;

      // in the other hand

      if (($people = $this->user->getPeople()) === null)
        return null;

      $peopleEmployee = $people->getPeopleCompany()->first();

      return $peopleEmployee === false ? $people : $peopleEmployee->getCompany();
    }

    return null;
  }

  private function getProvider(): ?People
  {
    if (!empty($this->params['myCompany']))
      return $this->em->find(People::class, $this->params['myCompany']);

    //if ($this->params['isMainCompany']) {
    $stateo  = $this->memo->ocity->getState();
    $pstates = $this->em->getRepository(PeopleStates::class)->findOneBy(['state' => $stateo]);

    if ($pstates)
      return $pstates->getPeople();
    //}

    if (!empty($this->params['domainAddress'])) {
      $domain = $this->em->getRepository(PeopleDomain::class)->findOneBy(['domain' => $this->params['domainAddress']]);
      if ($domain)
        return $domain->getPeople();
    }

    return $this->em->find(People::class, $this->params['companyId']);
  }

  private function createClient(array $data): People
  {
    try {

      $people = new People();
      $people->setName($data['name']);
      $people->setPeopleType('F');
      $people->setLanguage($this->em->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']));
      $people->setAlias('');
      $people->setEnabled(false);

      $this->em->persist($people);
      $this->em->flush($people);

      $email = new Email();
      $email->setPeople($people);
      $email->setEmail($data['email']);

      $this->em->persist($email);
      $this->em->flush($email);

      $phone = new Phone();
      $phone->setPeople($people);
      $phone->setDdd(substr($data['phone'], 0, 2));
      $phone->setPhone(substr($data['phone'], 2));

      $this->em->persist($phone);
      $this->em->flush($phone);

      return $people;
    } catch (\Exception $e) {
      throw new \Exception('There was an error trying to create people client');
    }
  }

  private function getAddress(AddressComponents $components): ?Address
  {
    if (false === $components->isFullAddress())
      return null;

    // city

    $city     = $this->getCity($components);
    if ($city === null)
      throw new \Exception(
        sprintf('Can not get address. City name "%s" was not found', $components->getCity())
      );

    // district

    $district = $this->em->getRepository(District::class)
      ->findOneBy(['district' => $components->getDistrict(), 'city' => $city]);

    if ($district === null) {
      $district = new District();

      $district->setDistrict($components->getDistrict());
      $district->setCity($city);

      $this->em->persist($district);
    }

    // cep

    $postalCode = $this->em->getRepository(Cep::class)->findOneBy(['cep' => $components->getPostalCode()]);
    if ($postalCode === null) {
      $postalCode = new Cep();

      $postalCode->setCep($components->getPostalCode());

      $this->em->persist($postalCode);
    }

    // street

    $street = null;

    if (!$this->entityIsNew($district))
      $street = $this->em->getRepository(Street::class)
        ->findOneBy(['street' => $components->getStreet(), 'district' => $district]);

    if ($street === null) {
      $street = new Street();

      $street->setStreet($components->getStreet());
      $street->setCep($postalCode);
      $street->setDistrict($district);

      $this->em->persist($street);
    }

    // address
    $address = null;

    if (!$this->entityIsNew($street) && !$this->entityIsNew($this->memo->client)) {
      $address  = $this->em->getRepository(Address::class)->findOneBy(
        [
          'people' => $this->memo->client,
          'street' => $street,
          'number' => $components->getNumber(),
        ]
      );
    }
    if ($address === null) {
      $address = new Address();

      $address->setComplement('');
      $address->setNickname('');
      $address->setNumber($components->getNumber());
      $address->setPeople($this->memo->client);
      $address->setStreet($street);
      $address->setLatitude(0);
      $address->setLongitude(0);

      $this->em->persist($address);
    }

    return $address;
  }

  private function getCity(AddressComponents $components): ?City
  {
    // country
    $country  = $this->em->getRepository(Country::class)->findOneBy(['countryname' => $components->getCountry()]);
    if ($country === null)
      throw new \Exception(
        sprintf('Country name "%s" was not found', $components->getCountry())
      );

    // state
    $state    = $this->em->getRepository(State::class)->findOneBy(['uf' => $components->getState(), 'country' => $country]);
    if ($state === null)
      throw new \Exception(
        sprintf('State name "%s" was not found', $components->getState())
      );

    // city
    $city     = $this->em->getRepository(City::class)->findOneBy(['city' => $components->getCity(), 'state' => $state]);

    return $city;
  }

  private function entityIsNew($entity): bool
  {
    return $entity->getId() === null;
  }

  private function getPeopleClient(): ?People
  {
    if (($company = $this->reqs->query->get('myCompany', null)) === null)
      return null;

    /**
     * @var User $currentUser
     */
    $currentUser  = $this->user;
    $clientPeople = $this->em->find(People::class, $company);

    if ($clientPeople === null)
      return null;

    // verify if client is a company of current user

    $isMyCompany = $currentUser->getPeople()->getPeopleCompany()->exists(
      function ($key, $element) use ($clientPeople) {
        return $element->getCompany() === $clientPeople;
      }
    );
    if ($isMyCompany === false)
      return null;

    return $clientPeople;
  }
}
