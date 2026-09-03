<?php
namespace App\Library\Provider\Signature\Clicksign\Resource;

use GuzzleHttp\Client;
use App\Library\Provider\Signature\Clicksign\Signer;
use App\Library\Provider\Signature\Clicksign\User;
use Exception;

class Notifications
{
  private $endpoint = '/notifications';

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  public function sendTo(Signer $signer): Signer
  {
    try {
      $options  = [
        'json' => [
          'request_signature_key' => $signer->getRequestSignatureKey(),
          'message'               => 'Por favor, assine o documento.',
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

      if ($response->getStatusCode() === 202) {
        return $signer;
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
