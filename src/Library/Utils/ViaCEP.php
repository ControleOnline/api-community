<?php

namespace App\Library\Utils;

use GuzzleHttp\Client;

class ViaCEP
{

    private static $__search_url = 'https://viacep.com.br/ws';
    private static $__key        = '';

    public static function setKey(string $key)
    {
        self::$__key = $key;
    }

    private static function getKey(): string
    {
        return self::$__key;
    }

    public static function search(string $cep, string $format = 'json'): ?object
    {
      try {

        $client   = new Client(['verify' => false]);
        $response = $client->get(sprintf('%s/%s/%s', self::$__search_url, $cep, $format));
        $result   = json_decode($response->getBody());

        if (isset($result->cep))
          return $result;

      } catch (\Exception $e) {

      }

      return null;
    }

    public static function isCEP(string $input): bool
    {
      return preg_match('/^\d{8}$/', $input) === 1;
    }
}
