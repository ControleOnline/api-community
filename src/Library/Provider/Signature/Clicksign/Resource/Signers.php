<?php
namespace App\Library\Provider\Signature\Clicksign\Resource;

use GuzzleHttp\Client;
use App\Library\Provider\Signature\Clicksign\Signer;
use App\Library\Provider\Signature\Clicksign\User;
use Exception;

class Signers
{
  private $endpoint = '/signers';

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  public function create(Signer $signer): Signer
  {
    try {
      $options  = [
        'json' => [
          'signer' => [
            'email'             => $signer->getEmail(),
            'auths'             => $signer->getAuths(),
            'name'              => $signer->getName(),
            'has_documentation' => $signer->getHasCPF(),
            'documentation'     => $signer->getCPF(),
            'birthday'          => $signer->getBirthday(),
            'delivery'          => $signer->getDelivery(),
          ],
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json',
        ],
      ];

      $response = (new Client(['verify' => false]))->post(
        sprintf(
          '%s?access_token=%s',
          $this->user->getHost() . $this->endpoint,
          $this->user->getToken()
        ),
        $options
      );

      if ($response->getStatusCode() === 201) {
        $result = json_decode($response->getBody());

        if (isset($result->signer)) {
          return $signer
            ->setKey($result->signer->key)
          ;
        }

        throw new Exception('Clicksign response format error');
      }

      throw new Exception('Clicksign response status invalid');

    } catch (Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ?
          json_decode($response->getBody()->getContents()) : null;

        if ($response->getStatusCode() === 422) {
          if (isset($contents->errors)) {
            throw new Exception($contents->errors[0]);
          }
        }
      }

      throw new Exception($e->getMessage());
    }
  }
}
