<?php
namespace App\Library\Provider\Signature\Zapsign\Resource;

use GuzzleHttp\Client;
use App\Library\Provider\Signature\Zapsign\Document;
use App\Library\Provider\Signature\Zapsign\User;
use App\Library\Provider\Signature\Zapsign\Signer;
use Exception;

class Documents
{
  private $endpoint = '/docs';

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  public function create(Document $document, bool $withSigners = false): Document
  {
    try {
      $options  = [
        'json' => [
          'sandbox'    => $this->user->isSandbox(),
          'name'       => $document->getFileName(),
          'base64_pdf' => $document->getBase64Content(),
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json',
        ],
      ];

      if ($withSigners) {
        if (!$document->hasSigners()) {
          throw new Exception('Zapsign signers list is empty');
        }

        $options['json']['signers'] = [];

        foreach ($document->getSigners() as $signer) {
          $options['json']['signers'][] = [
            'name'                 => $signer->getName(),
            'email'                => $signer->getEmail(),
            'send_automatic_email' => true,
            'lock_email'           => true,
            'lock_name'            => true,
          ];
        }
      }

      $response = (new Client(['verify' => false]))->post(
        sprintf(
          '%s/?api_token=%s',
          $this->user->getHost() . $this->endpoint,
          $this->user->getToken()
        ),
        $options
      );

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (isset($result->token)) {
          return $document
            ->setKey($result->token)
          ;
        }

        throw new Exception('Zapsign response format error');
      }

      throw new Exception('Zapsign response status invalid');

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

  public function addSignerToDocument(Document $document, Signer $signer): Document
  {
    try {
      $options  = [
        'json' => [
          'name'       => $signer->getName(),
          'email'      => $signer->getEmail(),
          'lock_name'  => true,
          'lock_email' => true,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json',
        ],
      ];
      $response = (new Client(['verify' => false]))->post(
        sprintf(
          '%s/%s/add-signer/?api_token=%s',
          $this->user->getHost(),
          $document->getKey(),
          $this->user->getToken()
        ),
        $options
      );

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        return $document;
      }

      throw new Exception('Zapsign response status invalid');

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
