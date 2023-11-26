<?php

namespace App\Library\Utils;

use App\Library\Utils\GMaps;
use App\Library\Utils\ViaCEP;

class Address
{
  private $address;

  private $result  = null;

  private $isReady = false;

  public function __construct($address)
  {
    if (is_string($address)) {
      $this->address = $address;

      if (ViaCEP::isCEP($address)) {
        $this->isReady = true;
      }
      else {
        if (isset($_ENV['GMAPS_KEY'])) {
          GMaps::setKey($_ENV['GMAPS_KEY']);
          $this->isReady = true;
        }
      }

    }
    else {
      if (is_array($address)) {
        $this->address = isset($address['address']) ? $address['address'] : '';

        // @hardcode
        if (strtolower($address['country']) == 'brasil')
          $address['country'] = 'Brazil';

        $this->result  = [
          'countryName' => isset($address['country'    ]) ? $address['country'    ] : '',
          'stateName'   => isset($address['state'      ]) ? $address['state'      ] : '',
          'cityName'    => isset($address['city'       ]) ? $address['city'       ] : '',
          'district'    => isset($address['district'   ]) ? $address['district'   ] : '',
          'street'      => isset($address['street'     ]) ? $address['street'     ] : '',
          'number'      => isset($address['number'     ]) ? $address['number'     ] : '',
          'postalCode'  => isset($address['postal_code']) ? $address['postal_code'] : '',
          'address'     => isset($address['address'    ]) ? $address['address'    ] : '',
          'complement'  => isset($address['complement' ]) ? $address['complement' ] : '',
        ];
      }
    }
  }

  public function isReady(): bool
  {
    return $this->isReady;
  }

  public function getCountry(): string
  {
    if ($this->result === null)
      $this->getResult();

    // @hardcode
    $country = $this->result['countryName'] == 'Brasil' ? 'Brazil' : $this->result['countryName'];

    return $this->result['countryName'];
  }

  public function getState(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['stateName'];
  }

  public function getCity(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['cityName'];
  }

  public function getDistrict(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['district'];
  }

  public function getStreet(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['street'];
  }

  public function getNumber(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['number'];
  }

  public function getPostalCode(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['postalCode'];
  }

  public function getComplement(): string
  {
    if ($this->result === null)
      $this->getResult();

    return $this->result['complement'];
  }

  public function isFullAddress(): bool
  {
      if ($this->result === null)
      return false;

    if (empty($this->result['countryName']))
      return false;

    if (empty($this->result['stateName']))
      return false;

    if (empty($this->result['cityName']))
      return false;

    if (empty($this->result['district']))
      return false;

    if (empty($this->result['street']))
      return false;

    if (!is_numeric($this->result['number']))
      return false;

    if (empty($this->result['postalCode']))
      return false;

    return true;
  }

  public function getComponents(): ?array
  {
    return $this->getResult();
  }

  private function getResult(): ?array
  {
    if ($this->result !== null)
      return $this->result;

    if (ViaCEP::isCEP($this->address)) {
      $address = ViaCEP::search($this->address);

      if ($address === null) {
        return null;
      }

      return $this->result = [
        'countryName' => 'Brazil',
        'stateName'   => $address->uf,
        'cityName'    => $address->localidade,
        'district'    => $address->bairro,
        'street'      => $address->logradouro,
        'number'      => '',
        'postalCode'  => $this->address,
        'address'     => '',
      ];
    }
    else {
      return $this->requestFromGMAPS();
    }
  }

  private function requestFromGMAPS(): array
  {
    $result = GMaps::geocode($this->address);

    if ($result === null)
      return null;

    if (!is_array($result->results) || empty($result->results))
      return null;

    if (!array_key_exists('address_components', $result->results[0]))
      return null;

    $components  = $result->results[0]->address_components;

    $countryName = array_filter($components, function($c) { return in_array('country'                    , $c->types); });
    $stateName   = array_filter($components, function($c) { return in_array('administrative_area_level_1', $c->types); });
    $cityName    = array_filter($components, function($c) { return in_array('administrative_area_level_2', $c->types); });
    $district    = array_filter($components, function($c) { return in_array('sublocality_level_1'        , $c->types); });
    $street      = array_filter($components, function($c) { return in_array('route'                      , $c->types); });
    $number      = array_filter($components, function($c) { return in_array('street_number'              , $c->types); });
    $postalCode  = array_filter($components, function($c) { return in_array('postal_code'                , $c->types); });

    $countryName = empty($countryName) ? false : current($countryName);
    $stateName   = empty($stateName  ) ? false : current($stateName  );
    $cityName    = empty($cityName   ) ? false : current($cityName   );
    $district    = empty($district   ) ? false : current($district   );
    $street      = empty($street     ) ? false : current($street     );
    $number      = empty($number     ) ? false : current($number     );
    $postalCode  = empty($postalCode ) ? false : current($postalCode );

    return $this->result = [
      'countryName' => $countryName === false ? '' : $countryName->long_name ,
      'stateName'   => $stateName   === false ? '' : $stateName  ->short_name,
      'cityName'    => $cityName    === false ? '' : $cityName   ->long_name ,
      'district'    => $district    === false ? '' : $district   ->long_name ,
      'street'      => $street      === false ? '' : $street     ->long_name ,
      'number'      => $number      === false ? '' : $number     ->long_name ,
      'postalCode'  => $postalCode  === false ? '' : $postalCode ->long_name ,
      'address'     => array_key_exists('formatted_address', $result->results[0]) ? $result->results[0]->formatted_address : '',
    ];
  }
}
