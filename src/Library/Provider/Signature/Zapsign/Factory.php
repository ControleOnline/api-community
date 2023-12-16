<?php

namespace App\Library\Provider\Signature\Zapsign;

use ControleOnline\Entity\Contract;
use App\Library\Provider\Signature\AbstractProvider;
use App\Library\Provider\Signature\Document as SignatureDocument;
use App\Library\Provider\Signature\Signer as SignatureSigner;
use App\Library\Provider\Signature\Zapsign\Resource\Documents;
use App\Library\Provider\Signature\Zapsign\Resource\Signers;

class Factory extends AbstractProvider
{
  protected $events = [
    'closed' => 'doc_signed',
  ];

  public function createDocument(): SignatureDocument
  {
    return new Document();
  }

  public function createSigner(): SignatureSigner
  {
    return new Signer();
  }
  public function getDocument(Contract $document)
  {
  }
  public function saveDocument(SignatureDocument $document): void
  {
    // get auth

    $auth = (new User)
      ->setHost('https://api.zapsign.com.br/api/v1')
      ->setToken($this->config['zapsign-token']);

    if (isset($this->config['zapsign-sandbox'])) {
      $auth->setIsSandbox(
        ((bool) $this->config['zapsign-sandbox'])
      );
    }

    // create document and signers

    (new Documents($auth))->create($document, true);
  }

  public function verifyEventPayload(string $eventName, object $payload): string
  {
    if (!isset($payload->event_type)) {
      throw new \Exception('Event parameter is not defined');
    }

    if ($payload->event_type != $this->events[$eventName]) {
      throw new \Exception('Event type is unacceptable');
    }

    if (!isset($payload->status)) {
      throw new \Exception('Status parameter is not defined');
    }

    if ($payload->status != 'signed') {
      throw new \Exception('Document status must be of type "signed"');
    }

    if (!isset($payload->token)) {
      throw new \Exception('Document token parameter is not defined');
    }

    return $payload->token;
  }
}
