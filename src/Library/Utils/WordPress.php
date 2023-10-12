<?php

namespace App\Library\Utils;

use GuzzleHttp\Client;

class WordPress
{

  private static $__wordpress        = '/wp-json/wp/v2/posts';

  public static function getPosts(array $input): array
  {
    $domain = 'https://www.controleonline.com';

    try {
      $client   = new Client(['verify' => false]);
      $response = $client->get($domain . self::$__wordpress, [
        'query' => $input
      ]);

      $result   = json_decode($response->getBody());

      if ($result)
        return $result;
    } catch (\Exception $e) {
      print_r($e);
    }

    return null;
  }
}
