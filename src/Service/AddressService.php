<?php

namespace App\Service;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\District;
use ControleOnline\Entity\People;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Street;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\GMapsService;
use DateTime;

class AddressService
{
  /**
   * Entity Manager
   *
   * @var GMapsService
   */
  private $gmaps = null;

  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  public function __construct(
    GMapsService $gmaps,
    EntityManagerInterface $entityManager
  ) {
    $this->gmaps   = $gmaps;
    $this->manager = $entityManager;
  }

  public function createFor(People $people, array $address): ?Address
  {
    if ($this->isFullAddress($address)) {
      return $this->getAddress($address, $people);
    }

    return null;
  }

  public function create(array $address): ?Address
  {
    if ($this->isFullAddress($address)) {
      return $this->getAddress($address, null);
    }

    return null;
  }

  public function isFullAddress(array $address): bool
  {
    if (!isset($address['country']) || empty($address['country']))
      throw new \InvalidArgumentException('Parameter "address country" is missing');

    if (!isset($address['state']) || empty($address['state']))
      throw new \InvalidArgumentException('Parameter "address state" is missing');

    if (!isset($address['city']) || empty($address['city']))
      throw new \InvalidArgumentException('Parameter "address city" is missing');

    if (!isset($address['district']) || empty($address['district']))
      throw new \InvalidArgumentException('Parameter "address district" is missing');

    if (!isset($address['street']) || empty($address['street']))
      throw new \InvalidArgumentException('Parameter "address street" is missing');

    if (!isset($address['postalCode']) || empty($address['postalCode']))
      throw new \InvalidArgumentException('Parameter "address postalCode" is missing');
    else if (preg_match('/^[0-9]{8}$/', $address['postalCode']) !== 1)
      throw new \InvalidArgumentException('Parameter "address postalCode" is not valid');

    if (!isset($address['number']) || !is_numeric($address['number']))
      throw new \InvalidArgumentException('Parameter "address number" is missing');

    return true;
  }

  public function addressToArray(Address $address): array
  {
    $street     = $address->getStreet();
    $district   = $street->getDistrict();
    $city       = $district->getCity();
    $state      = $city->getState();
    $postalCode = $street->getCep()->getCep();
    $postalCode = strlen($postalCode) == 7 ? '0' . $postalCode : $postalCode;

    return [
      'id'         => $address->getId(),
      'nickname'   => $address->getNickname(),
      'country'    => strtolower($state->getCountry()->getCountryName()) == 'brazil' ? 'Brasil' : $state->getCountry()->getCountryName(),
      'state'      => $state->getUF(),
      'city'       => $city->getCity(),
      'district'   => $district->getDistrict(),
      'postalCode' => $postalCode,
      'street'     => $street->getStreet(),
      'number'     => $address->getNumber(),
      'complement' => $address->getComplement(),
      'lat'        => $address->getLatitude(),
      'lng'        => $address->getLongitude(),
      'locator'        => $address->getLocator(),
      'openingTime'  => $address->getOpeningTime() ? $address->getOpeningTime()->format('H:i') : $address->getOpeningTime(),
      'closingTime'  => $address->getClosingTime() ? $address->getClosingTime()->format('H:i') : $address->getClosingTime(),
      'searchFor'      => $address->getSearchFor()
    ];
  }

  private function getAddress(array $components, ?People $people): Address
  {
    // search city

    $city = $this->getCity($components);
    if ($city === null)
      throw new \InvalidArgumentException(
        sprintf('Cidade com nome "%s" não foi encontrada', $components['city'])
      );

    // search district

    $district = $this->manager->getRepository(District::class)
      ->findOneBy(['district' => $components['district'], 'city' => $city]);

    if ($district === null) {
      $district = new District();

      $district->setDistrict($components['district']);
      $district->setCity($city);

      $this->manager->persist($district);
    }

    // search postal code

    $postalCode = $this->manager->getRepository(Cep::class)
      ->findOneBy(['cep' => $this->fixPostalCode($components['postalCode'])]);

    if ($postalCode === null) {
      $postalCode = new Cep();

      $postalCode->setCep($components['postalCode']);

      $this->manager->persist($postalCode);
    }

    // search street

    $street = null;

    if (!$this->entityIsNew($district)) {
      $street = $this->manager->getRepository(Street::class)
        ->findOneBy(['street' => $components['street'], 'district' => $district]);
    }

    if ($street === null) {
      $street = new Street();

      $street->setStreet($components['street']);
      $street->setCep($postalCode);
      $street->setDistrict($district);

      $this->manager->persist($street);
    }

    // search address

    /**
     * @var Address
     */
    $address = null;

    $gMapsItems = $this->gmaps->search(
      $components['street'] . ', ' .
        $components['number'] . ' - ' .
        $components['district'] . '. ' .
        $components['city'] . '-' .
        $city->getState()->getUf()
    );

    if ($people instanceof People) {
      if ($this->entityIsNew($people) || $this->entityIsNew($street)) {
        $address = new Address();

        $address->setComplement(isset($components['complement']) ? $components['complement'] : '');
        $address->setNickname(isset($components['nickname']) ? $components['nickname'] : '');
        $address->setNumber($components['number']);
        $address->setPeople($people);
        $address->setStreet($street);

        if (!empty($gMapsItems) && !empty($gMapsItems[0])) {
          $address->setLatitude($gMapsItems[0]->lat);
          $address->setLongitude($gMapsItems[0]->lng);
        } else {
          $address->setLatitude(0);
          $address->setLongitude(0);
        }
        $this->manager->persist($address);
      } else {

        // if people and street already exists

        if (!$this->entityIsNew($people) && !$this->entityIsNew($street)) {
          $address = $this->manager->getRepository(Address::class)
            ->findOneBy(['people' => $people, 'street' => $street, 'number' => $components['number']]);

          // if address is not associated to people

          if ($address === null) {
            $address = new Address();

            $address->setComplement(isset($components['complement']) ? $components['complement'] : '');
            $address->setNickname(isset($components['nickname']) ? $components['nickname'] : '');
            $address->setNumber($components['number']);
            $address->setPeople($people);
            $address->setStreet($street);

            if (!empty($gMapsItems) && !empty($gMapsItems[0])) {
              $address->setLatitude($gMapsItems[0]->lat);
              $address->setLongitude($gMapsItems[0]->lng);
            } else {
              $address->setLatitude(0);
              $address->setLongitude(0);
            }

            $address->setLocator($components['locator']);
            $address->setOpeningTime($components['openingTime'] ? DateTime::createFromFormat('H:i', $components['openingTime']) : null);
            $address->setClosingTime($components['closingTime'] ? DateTime::createFromFormat('H:i', $components['closingTime']) : null);
            $address->setSearchFor($components['searchFor'] ? $components['searchFor'] : null);

            $this->manager->persist($address);
          }
        }
      }
    } else {
      $address = new Address();

      $address->setComplement(isset($components['complement']) ? $components['complement'] : '');
      $address->setNickname(isset($components['nickname']) ? $components['nickname'] : '');
      $address->setNumber($components['number']);
      $address->setPeople(null);
      $address->setStreet($street);
      $address->setLatitude(0);
      $address->setLongitude(0);
      $address->setLocator($components['locator']);
      $address->setOpeningTime($components['openingTime'] ? DateTime::createFromFormat('H:i', $components['openingTime']) : null);
      $address->setClosingTime($components['closingTime'] ? DateTime::createFromFormat('H:i', $components['closingTime']) : null);
      $address->setSearchFor($components['searchFor'] ? $components['searchFor'] : null);

      $this->manager->persist($address);
    }

    return $address;
  }

  private function getCity(array $components): ?City
  {
    $country = $this->manager->getRepository(Country::class)
      ->findOneBy(['countryname' => $this->fixCountryName($components['country'])]);
    if ($country === null)
      throw new \InvalidArgumentException(
        sprintf('País com nome "%s" não foi encontrado', $components['country'])
      );

    // search by UF

    $state = $this->manager->getRepository(State::class)
      ->findOneBy(['uf' => $components['state'], 'country' => $country]);
    if ($state === null) {

      // search by name

      $state = $this->manager->getRepository(State::class)
        ->findOneBy(['state' => $components['state'], 'country' => $country]);

      if ($state === null) {
        throw new \InvalidArgumentException(
          sprintf('Estado com nome "%s" não foi encontrado', $components['state'])
        );
      }
    }

    return $this->manager->getRepository(City::class)
      ->findOneBy(['city' => $components['city'], 'state' => $state]);
  }

  // @todo fix postal code
  private function fixPostalCode(string $postalCode): string
  {
    return strpos($postalCode, '0') === 0 ? substr($postalCode, 1) : $postalCode;
  }

  // @todo fix country name
  private function fixCountryName(string $originalName): string
  {
    return strtolower($originalName) == 'brasil' ? 'brazil' : $originalName;
  }

  private function entityIsNew($entity): bool
  {
    return $entity->getId() === null;
  }
}
