<?php

namespace App\Library\Provider\Signature\Clicksign;

use ControleOnline\Entity\Contract;
use App\Library\Provider\Signature\AbstractProvider;
use App\Library\Provider\Signature\Document as SignatureDocument;
use App\Library\Provider\Signature\Signer as SignatureSigner;
use App\Library\Provider\Signature\Clicksign\Resource\Documents;
use App\Library\Provider\Signature\Clicksign\Resource\Signers;
use App\Library\Provider\Signature\Clicksign\Resource\Notifications;
use Exception;

class Factory extends AbstractProvider
{
  protected $events = [
    'closed' => 'auto_close',
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
    // get auth

    $auth = (new User)->setToken($this->config['clicksign-token']);

    if (isset($this->config['clicksign-sandbox'])) {
      $auth->setIsSandbox(
        ((bool) $this->config['clicksign-sandbox'])
      );
    }

    $auth->setHost(
      $auth->isSandbox() ?
        'https://sandbox.clicksign.com/api/v1' : 'https://app.clicksign.com/api/v1'
    );


    $documents = new Documents($auth);
    return $documents->getDocument($document);
  }

  public function saveDocument(SignatureDocument $document): void
  {
    // get auth

    $auth = (new User)->setToken($this->config['clicksign-token']);

    if (isset($this->config['clicksign-sandbox'])) {
      $auth->setIsSandbox(
        ((bool) $this->config['clicksign-sandbox'])
      );
    }

    $auth->setHost(
      $auth->isSandbox() ?
        'https://sandbox.clicksign.com/api/v1' : 'https://app.clicksign.com/api/v1'
    );

    // create document

    $documents = new Documents($auth);
    $documents->create($document);

    // create signers

    $signers  = new Signers($auth);
    foreach ($document->getSigners() as $signer) {
      $documents->addSignerToDocument(
        $document,
        $signers->create($signer)
      );
    }

    // notify signers (via email)

    $notifies = new Notifications($auth);
    foreach ($document->getSigners() as $signer) {
      $notifies->sendTo($signer);
    }
  }

  public function verifyEventPayload(string $eventName, object $payload): string
  {
    if (!isset($payload->event)) {
      throw new Exception('Event parameter is not defined');
    }

    if ($payload->event->name != $this->events[$eventName]) {
      throw new Exception('Event type is unacceptable');
    }

    if (!isset($payload->document)) {
      throw new Exception('Document parameter is not defined');
    }

    if (!isset($payload->document->key)) {
      throw new Exception('Document key parameter is invalid');
    }

    return $payload->document->key;
  }
}
