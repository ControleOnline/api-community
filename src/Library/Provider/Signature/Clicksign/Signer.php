<?php
namespace App\Library\Provider\Signature\Clicksign;

use App\Library\Provider\Signature\Signer as SignatureSigner;

class Signer implements SignatureSigner
{
  private $key          = '';

  private $email        = '';

  private $auths        = ['email'];

  private $name         = '';

  private $cpf          = '';

  private $birthday     = '0000-00-00';

  private $hasCPF       = false;

  private $delivery     = 'email';

  private $signatureKey = '';

  public function setKey(string $key): self
  {
    $this->key = $key;

    return $this;
  }

  public function getKey(): string
  {
    return $this->key;
  }

  public function setEmail(string $email): self
  {
    $this->email = $email;

    return $this;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function setCPF(string $cpf): self
  {
    $this->cpf = $cpf;

    return $this;
  }

  public function getCPF(): string
  {
    return $this->cpf;
  }

  public function setHasCPF(bool $hasCPF): self
  {
    $this->hasCPF = $hasCPF;

    return $this;
  }

  public function getHasCPF(): bool
  {
    return $this->hasCPF;
  }

  public function setBirthday(string $birthday): self
  {
    $this->birthday = $birthday;

    return $this;
  }

  public function getBirthday(): string
  {
    return $this->birthday;
  }

  public function getAuths(): array
  {
    return $this->auths;
  }

  public function getDelivery(): string
  {
    return $this->delivery;
  }

  public function setRequestSignatureKey(string $key): self
  {
    $this->signatureKey = $key;

    return $this;
  }

  public function getRequestSignatureKey(): string
  {
    return $this->signatureKey;
  }
}
