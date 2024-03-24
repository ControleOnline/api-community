<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\Company;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleEmployee;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\State;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\District;
use ControleOnline\Entity\Street;
use ControleOnline\Entity\Cep;
use App\Library\Utils\Address as AddressComponents;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\PeopleStates;

class CreateCompanyHandler implements MessageHandlerInterface
{
  const DOC_CPF  = 'CPF';
  const DOC_CNPJ = 'CNPJ';

  private $manager;

  /**
   * @var User
   */
  private $user;

  private $memo;

  public function __construct(EntityManagerInterface $manager, Security $security)
  {
    $this->manager = $manager;
    $this->user    = $security->getUser();
    $this->memo    = new \App\Library\Utils\Memory();
  }

  public function __invoke(Company $company)
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $company = $this->createCompany($company);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => [
            'people'   => [
              'id' => $company->getId(),
            ],
          ],
          'count'   => 1,
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
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

  private function createCompany(Company $company): People
  {
    if (!$this->user->getPeople() instanceof People)
      throw new \Exception('People user is not linked');

    $cpeople = $this->getPeopleByDocument($company->document);

    if ($cpeople instanceof People) {

      // verify if company has employees

      if ($cpeople->getCompany()->count() > 0)
        throw new \Exception('This company already exists');

      // if company exists but it doesnt have any employees

      $employee = new PeopleEmployee();
      $employee->setEmployee($this->user->getPeople());
      $employee->setCompany ($cpeople);
      $employee->setEnabled (1);
      $this->manager->persist($employee);

      $this->updateClientOrder();

      return $cpeople;
    }
    else {
      $people = $this->getPeopleFromDocument($company);
      $this->manager->persist($people);

      $this->memo->add('client', $people);

      $employee = new PeopleEmployee();
      $employee->setEmployee($this->user->getPeople());
      $employee->setCompany ($people);
      $employee->setEnabled (1);
      $this->manager->persist($employee);

      $document = new Document();
      $document->setDocument    ($company->document);
      $document->setDocumentType($this->getPeopleDocumentType($company->document));
      $document->setPeople      ($people);
      $this->manager->persist($document);

      $client = $this->getPeopleClient($people, $this->getMainCompany());
      $client->setEnabled(1);
      $this->manager->persist($client);

      // if it's main company
      if ($client->getCompanyId() == $this->getMainCompany()->getId() && is_array($company->origin)) {

        // search by an enterprise that attends state of origin

        $address = new AddressComponents($company->address);
        $cOrigin = $this->getCity($address);
        $pstates = $this->manager->getRepository(PeopleStates::class)->findBy(['state' => $cOrigin->getState()]);

        // then, it creates a client for that enterprise

        if (!empty($pstates) && $pstates[0]->getPeople()->getId() != $this->getMainCompany()->getId()) {
          $client2 = $this->getPeopleClient($people, $pstates[0]->getPeople());
          $client2->setEnabled(1);
          $this->manager->persist($client2);
        }
      }

      $this->createAdress($company->address);

      $this->updateClientOrder();

      return $people;
    }
  }

  private function getPeopleClient(People $client, People $company): PeopleClient
  {
    $peopleClient = $this->manager->getRepository(PeopleClient::class)
      ->findOneBy([
        'client'     => $client,
        'company_id' => $company->getId(),
      ]);

    if ($peopleClient instanceof PeopleClient)
        return $peopleClient;

    $peopleClient = new PeopleClient();
    $peopleClient->setCompanyId($company->getId());
    $peopleClient->setClient   ($client);
    $peopleClient->setEnabled  (1);

    return $peopleClient;
  }

  private function getPeopleFromDocument(Company $company): People
  {
    if ($this->getDocumentType($company->document) == self::DOC_CNPJ) {
      $people = new People();
      $people->setName       ($company->name);
      $people->setAlias      ($company->alias);
      $people->setPeopleType ('J');
      $people->setLanguage   ($this->getDefaultLanguage());
      $people->setEnabled    (1);
    }
    else {
      $people = $this->user->getPeople();
      $people->setName      ($company->name);
      $people->setAlias     ($company->alias);
      $people->setPeopleType('F');
      $people->setLanguage  ($this->getDefaultLanguage());
      $people->setEnabled   (1);
    }

    return $people;
  }

  private function getPeopleByDocument(string $document): ?People
  {
    $docType  = $this->getPeopleDocumentType($document);
    $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $document, 'documentType' => $docType]);

    return $document instanceof Document ? $document->getPeople() : null;
  }

  private function getDefaultLanguage(): ?Language
  {
    return $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
  }

  private function getPeopleDocumentType(string $document): ?DocumentType
  {
    $filter = [
      'documentType' => $this->getDocumentType($document),
      'peopleType'   => $this->getDocumentType($document) == self::DOC_CNPJ ? 'J' : 'F',
    ];

    return $this->manager->getRepository(DocumentType::class)->findOneBy($filter);
  }

  private function getDocumentType(string $document): ?string
  {
    return strlen($document) == 14 ? self::DOC_CNPJ : (strlen($document) == 11 ? self::DOC_CPF : null);
  }

  private function getMainCompany(): People
  {
    $domain  = $_SERVER['HTTP_HOST'];
    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      throw new \Exception(
        sprintf('Main company "%s" not found', $domain)
      );

    return $company->getPeople();
  }

  private function updateClientOrder()
  {
    // get orders associated to current people
    $orders = $this->manager->getRepository(SalesOrder::class)->findBy(['client' => $this->user->getPeople()]);

    if (empty($orders) === false) {
      foreach ($orders as $order) {
        $order->setClient($this->memo->client);
        $this->manager->persist($order);

        foreach ($order->getQuotes() as $quote) {
          $quote->setClient($this->memo->client);
          $this->manager->persist($quote);
        }
      }
    }
  }

  private function createAdress(array $address)
  {
    $components = new AddressComponents($address);

    if (false === $components->isFullAddress())
      throw new \Exception('Missing address components');

    $this->persistAddress($components);
  }

  private function persistAddress(AddressComponents $components)
  {
    // city
    $city = $this->getCity($components);
    if ($city === null)
      throw new \Exception(
        sprintf('Can not get address. City name "%s" was not found', $components->getCity())
      );

    // district
    $district = $this->manager->getRepository(District::class)->findOneBy(
      [
        'district' => $components->getDistrict(),
        'city'     => $city,
      ]
    );
    if ($district === null) {
      $district = new District();

      $district->setDistrict($components->getDistrict());
      $district->setCity    ($city);

      $this->manager->persist($district);
    }

    // cep
    $postalCode = $this->manager->getRepository(Cep::class)->findOneBy(['cep' => $components->getPostalCode()]);
    if ($postalCode === null) {
      $postalCode = new Cep();

      $postalCode->setCep($components->getPostalCode());

      $this->manager->persist($postalCode);
    }

    // street

    $street = null;

    if (!$this->entityIsNew($district)) {
      $street = $this->manager->getRepository(Street::class)
        ->findOneBy(['street' => $components->getStreet(), 'district' => $district]);
    }

    if ($street === null) {
      $street = new Street();

      $street->setStreet  ($components->getStreet());
      $street->setCep     ($postalCode);
      $street->setDistrict($district);

      $this->manager->persist($street);
    }

    // search address

    $address = null;

    if ($this->entityIsNew($this->memo->client) || $this->entityIsNew($street)) {
      $address = new Address();

      $address->setComplement($components->getComplement());
      $address->setNickname  ('');
      $address->setNumber    ($components->getNumber());
      $address->setPeople    ($this->memo->client);
      $address->setStreet    ($street);
      $address->setLatitude  (0);
      $address->setLongitude (0);

      $this->manager->persist($address);
    }
    else {

      // if people and street already exists

      if (!$this->entityIsNew($this->memo->client) && !$this->entityIsNew($street)) {
        $address = $this->manager->getRepository(Address::class)->findOneBy(['people' => $this->memo->client, 'street' => $street]);

        // if address is not associated to people

        if ($address === null) {
          $address = new Address();

          $address->setComplement($components->getComplement());
          $address->setNickname  ('');
          $address->setNumber    ($components->getNumber());
          $address->setPeople    ($this->memo->client);
          $address->setStreet    ($street);
          $address->setLatitude  (0);
          $address->setLongitude (0);

          $this->manager->persist($address);
        }
      }
    }
  }

  private function getCity(AddressComponents $components): ?City
  {
    // country
    $country  = $this->manager->getRepository(Country::class)->findOneBy(['countryname' => $components->getCountry()]);
    if ($country === null)
      throw new \Exception(
        sprintf('Country name "%s" was not found', $components->getCountry())
      );

    // state
    $state    = $this->manager->getRepository(State::class   )->findOneBy(['uf' => $components->getState(), 'country' => $country]);
    if ($state === null)
      throw new \Exception(
        sprintf('State name "%s" was not found', $components->getState())
      );

    // city
    $city     = $this->manager->getRepository(City::class    )->findOneBy(['city' => $components->getCity(), 'state' => $state]);

    return $city;
  }

  private function entityIsNew($entity): bool
  {
    return $entity->getId() === null;
  }
}
