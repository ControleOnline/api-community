<?php
namespace App\Library\Provider\Signature\Clicksign;

use App\Library\Provider\Signature\Document as SignatureDocument;
use App\Library\Provider\Signature\Signer;

class Document implements SignatureDocument
{
  private $key         = '';

  private $fileName    = '';

  private $content     = '';

  private $deadline_at = '2019-11-28T14:30:59-03:00';

  private $signers     = [];

  public function setKey(string $key): self
  {
    $this->key = $key;

    return $this;
  }

  public function getKey(): string
  {
    return $this->key;
  }

  public function setFileName(string $fileName)
  {
    $this->fileName = $fileName;

    return $this;
  }

  public function getFileName(): string
  {
    return $this->fileName;
  }

  public function getPath(): string
  {
    return '/' . $this->getFileName() . '.pdf';
  }

  public function setContent(string $content): self
  {
    $this->content = $content;

    return $this;
  }

  public function getContent(): string
  {
    return $this->content;
  }

  public function getBase64Content(): string
  {
    return 'data:application/pdf;base64,' . base64_encode($this->content);
  }

  public function setDeadlineAt(string $deadlineAt): self
  {
    $this->deadline_at = $deadlineAt;

    return $this;
  }

  public function getDeadlineAt(): string
  {
    return $this->deadline_at;
  }

  public function addSigner(Signer $signer): self
  {
    if (!isset($this->signers[$signer->getKey()])) {
      $this->signers[$signer->getKey()] = $signer;
    }

    return $this;
  }

  public function getSigners(): array
  {
    return $this->signers;
  }

  public function hasSigners(): bool
  {
    return !empty($this->signers);
  }
}
