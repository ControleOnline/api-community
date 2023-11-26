<?php
namespace App\Library\Postalcode\GoogleMaps;

use App\Library\Postalcode\Entity\Address;
use GuzzleHttp\Client;
use App\Library\Postalcode\Exception\InvalidParameterException;
use App\Library\Postalcode\Exception\ProviderRequestException;
use App\Library\Postalcode\PostalcodeService;

class GoogleMapsService implements PostalcodeService
{
  private $key      = '';
  private $endpoint = 'https://maps.googleapis.com/maps/api/geocode/json';

  public function __construct()
  {
    $this->key = $_ENV['GMAPS_KEY'];
  }

  public function query(string $postalCode): Address
  {
    if (!$this->isCEP($postalCode)) {
      throw new InvalidParameterException('CEP string is not valid. Acceptable format: 16058741');
    }

    $result     = $this->search($postalCode);
    $components = $result->results[0]->address_components;

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

    return (new Address)
      ->setCountry   ('Brasil')
      ->setState     ($stateName  === false ? '' : $stateName ->short_name)
      ->setUF        ($stateName  === false ? '' : $stateName ->short_name)
      ->setCity      ($cityName   === false ? '' : $cityName  ->long_name )
      ->setDistrict  ($district   === false ? '' : $district  ->long_name )
      ->setStreet    ($street     === false ? '' : $street    ->long_name )
      ->setNumber    ($number     === false ? '' : $number    ->long_name )
      ->setPostalCode($postalCode === false ? '' : $postalCode->long_name )
      ->setComplement('')
    ;
  }

  private function search(string $cep): object
  {
    try {
      $client   = new Client(['verify' => false]);
      $response = $client->request('GET',$this->endpoint, [
        'query' => [
          'address'  => $cep,
          'key'      => $this->key,
          'language' => 'pt-BR',
          'region'   => 'br',
        ]
      ]);
      $result   = json_decode($response->getBody());

      if (isset($result->status) && $result->status === 'OK') {
        if (!is_array($result->results) || empty($result->results)) {
          throw new ProviderRequestException('Google Maps response without results');
        }

        if (!array_key_exists('address_components', $result->results[0])) {
          throw new ProviderRequestException('Google Maps response without address components');
        }

        return $result;
      }

      throw new ProviderRequestException('Google Maps response error');
    } catch (\Exception $e) {
      throw new ProviderRequestException($e->getMessage());
    }
  }

  private function isCEP(string $input): bool
  {
    return preg_match('/^\d{8}$/', $input) === 1;
  }
}
