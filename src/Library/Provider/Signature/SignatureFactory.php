<?php
namespace App\Library\Provider\Signature;

interface SignatureFactory
{
  public function createDocument(): Document;

  public function createSigner(): Signer;

  public function saveDocument(Document $document): void;

  public function verifyEventPayload(string $eventName, object $payload): string;
}
