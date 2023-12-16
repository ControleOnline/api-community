<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Library\Utils\GMaps;
use ControleOnline\Entity\GeoPlace;

class GMapsService
{

  public function __construct(RequestStack $request)
  {
    $this->rq = $request->getCurrentRequest();

    if (!isset($_ENV['GMAPS_KEY']))
      throw new \Exception('GMAPS key is not defined');

    GMaps::setKey($_ENV['GMAPS_KEY']);
  }

  public function search(string $input): array
  {
    $items  = [];
    $result = GMaps::geocode($this->fixInput($input));
    // dd($this->fixInput($input));

    if ($result === null)
      return $items;

    if (!is_array($result->results) || empty($result->results))
      return $items;

    if (!array_key_exists('address_components', (array) $result->results[0]))
      return $items;

    // create address collection

    foreach ($result->results as $address) {
      $components = $this->extractComponents($address);

      $geoplace              = new GeoPlace();
      $geoplace->id          = $components['place_id'   ];
      $geoplace->description = $components['address'    ];
      $geoplace->country     = $components['country'    ];
      $geoplace->state       = $components['state'      ];
      $geoplace->city        = $components['city'       ];
      $geoplace->district    = $components['district'   ];
      $geoplace->street      = $components['street'     ];
      $geoplace->number      = $components['number'     ];
      $geoplace->lat         = $components['lat'        ];
      $geoplace->lng         = $components['lng'        ];
      $geoplace->postal_code = preg_replace('/[^0-9]/', '', $components['postal_code']);

      $items[] = $geoplace;
    }

    return $items;
  }

  private function fixInput(string $input): string
  {
    if (preg_match('/^\d{8}$/', $input) === 1)
      return preg_replace('/^(\d{5})(\d{3})$/i', '$1-$2', $input);

    return $input;
  }

  private function extractComponents(object $address): array
  {
    $components = $address->address_components;
    $geometry   = $address->geometry;

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

    $latLng = null;
    
    if (!empty($geometry->location)) {
      $latLng = $geometry->location;
    }
    else {
      $locations = !empty($geometry->bounds) ? $geometry->bounds : $geometry->viewport;

      if (!empty($locations)) {
        $latLng = !empty($locations->northeast) ? $locations->northeast : $locations->southwest;
      }
    }

    return [
      'place_id'    => $address->place_id,
      'country'     => $countryName === false ? '' : $countryName->long_name ,
      'state'       => $stateName   === false ? '' : $stateName  ->short_name,
      'city'        => $cityName    === false ? '' : $cityName   ->long_name ,
      'district'    => $district    === false ? '' : $district   ->long_name ,
      'street'      => $street      === false ? '' : $street     ->long_name ,
      'number'      => $number      === false ? '' : $number     ->long_name ,
      'postal_code' => $postalCode  === false ? '' : $postalCode ->long_name ,
      'address'     => $address->formatted_address,
      'lat'         => !empty($latLng) ? $latLng->lat : null,
      'lng'         => !empty($latLng) ? $latLng->lng : null
    ];
  }
}
