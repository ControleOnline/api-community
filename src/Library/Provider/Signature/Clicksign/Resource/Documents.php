<?php

namespace App\Library\Provider\Signature\Clicksign\Resource;

use GuzzleHttp\Client;
use App\Library\Provider\Signature\Clicksign\Document;
use App\Library\Provider\Signature\Clicksign\User;
use App\Library\Provider\Signature\Clicksign\Signer;
use ControleOnline\Entity\Contract;
use Exception;

class Documents
{
  private $endpoint = '/documents';

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  public function create(Document $document): Document
  {
    try {
      $options  = [
        'json' => [
          'document' => [
            'path'           => $document->getPath(),
            'content_base64' => $document->getBase64Content(),
            'deadline_at'    => $document->getDeadlineAt(),
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

        if (isset($result->document)) {
          return $document
            ->setKey($result->document->key);
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


  public function getDocument(Contract $document)
  {
    try {
      $options  = [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json',
        ],
      ];
      $response = (new Client(['verify' => false]))->get(
        sprintf(
          '%s?access_token=%s',
          $this->user->getHost() . $this->endpoint . '/' . $document->getKey(),
          $this->user->getToken()
        ),
        $options
      );

      if ($response->getStatusCode() === 200) {
        return json_decode($response->getBody());

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


  public function addSignerToDocument(Document $document, Signer $signer): Document
  {
    try {
      $options  = [
        'json' => [
          'list' => [
            'document_key' => $document->getKey(),
            'signer_key'   => $signer->getKey(),
            'sign_as'      => 'sign',
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
          $this->user->getHost() . '/lists',
          $this->user->getToken()
        ),
        $options
      );

      if ($response->getStatusCode() === 201) {
        $result = json_decode($response->getBody());

        if (isset($result->list)) {
          $signer
            ->setRequestSignatureKey(
              $result->list->request_signature_key
            );

          return $document;
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
