<?php
namespace App\Library\Provider\Signature;

interface Signer
{
  public function setKey(string $key);

  public function getKey(): string;

  public function setEmail(string $email);

  public function getEmail(): string;

  public function setName(string $name);

  public function getName(): string;

  public function setCPF(string $cpf);

  public function getCPF(): string;

  public function setHasCPF(bool $hasCPF);

  public function getHasCPF(): bool;

  public function setBirthday(string $birthday);

  public function getBirthday(): string;

  public function getAuths(): array;

  public function getDelivery(): string;

  public function setRequestSignatureKey(string $key);

  public function getRequestSignatureKey(): string;
}
