<?php

namespace App\Library\Provider\Signature;

interface Document
{
  public function addSigner(Signer $signer);

  public function getSigners(): array;

  public function setKey(string $key);

  public function getKey(): string;

  public function setFileName(string $fileName);

  public function getFileName(): string;

  public function getPath(): string;

  public function setContent(string $content);

  public function getContent(): string;

  public function getBase64Content(): string;

  public function setDeadlineAt(string $deadlineAt);

  public function getDeadlineAt(): string;

  public function hasSigners(): bool;
}
