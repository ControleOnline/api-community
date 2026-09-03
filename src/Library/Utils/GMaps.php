<?php

namespace App\Library\Utils;

use GuzzleHttp\Client;

class GMaps
{

    private static $__geocode_url        = 'https://maps.googleapis.com/maps/api/geocode/json';
    private static $__distancematrix_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    private static $__placeautocomplete  = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    private static $__key                = '';

    public static function setKey(string $key)
    {
        self::$__key = $key;
    }

    private static function getKey(): string
    {
        return self::$__key;
    }

    /**
     * Returns distance between 2 points in meters
     */
    public static function distanceMatrix(string $originAddress, string $destinationAddress)
    {
      try {

        $client   = new Client(['verify' => false]);
        $response = $client->get(self::$__distancematrix_url, [
          'query' => [
            'key'          => self::getKey(),
            'origins'      => $originAddress,
            'destinations' => $destinationAddress
          ]
        ]);
        $result   = json_decode($response->getBody());

        if ($result->status == 'OK')
          return $result->rows[0]->elements[0]->distance->value;

      } catch (\Exception $e) {

      }

      return null;
    }

    public static function geocode(string $address): ?object
    {
      try {

        $client   = new Client(['verify' => false]);
        $response = $client->request('GET',self::$__geocode_url, [
          'query' => [
            'address'  => $address,
            'key'      => self::getKey(),
            'language' => 'pt-BR',
            'region'   => 'br',
          ]
        ]);
        $result   = json_decode($response->getBody());

        if ($result->status == 'OK')
          return $result;

      } catch (\Exception $e) {

      }

      return null;
    }

    public static function placeautocomplete(string $input): ?object
    {
      try {

        $client   = new Client(['verify' => false]);
        $response = $client->get(self::$__placeautocomplete, [
          'query' => [
            'input'      => $input,
            'language'   => 'pt-BR',
            'components' => 'country:br',
            'key'        => self::getKey(),
          ]
        ]);
        $result   = json_decode($response->getBody());

        if ($result->status == 'OK')
          return $result;

      } catch (\Exception $e) {

      }

      return null;
    }
}
