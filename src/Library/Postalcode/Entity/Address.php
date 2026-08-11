<?php
namespace App\Library\Postalcode\Entity;

class Address
{
  private $country    = '';

  private $state      = '';

  private $uf         = '';

  private $city       = '';

  private $district   = '';

  private $street     = '';

  private $number     = '';

  private $postalCode = '';

  private $complement = '';

  public function setCountry(string $country): self
  {
    $this->country = $country;

    return $this;
  }

  public function getCountry(): string
  {
    return $this->country;
  }

  public function setState(string $state): self
  {
    $this->state = $state;

    return $this;
  }

  public function getState(): string
  {
    return $this->state;
  }

  public function setUF(string $uf): self
  {
    $this->uf = $uf;

    return $this;
  }

  public function getUF(): string
  {
    return $this->uf;
  }

  public function setCity(string $city): self
  {
    $this->city = $city;

    return $this;
  }

  public function getCity(): string
  {
    return $this->city;
  }

  public function setDistrict(string $district): self
  {
    $this->district = $district;

    return $this;
  }

  public function getDistrict(): string
  {
    return $this->district;
  }

  public function setStreet(string $street): self
  {
    $this->street = $street;

    return $this;
  }

  public function getStreet(): string
  {
    return $this->street;
  }

  public function setNumber(string $number): self
  {
    $this->number = $number;

    return $this;
  }

  public function getNumber(): string
  {
    return $this->number;
  }

  public function setPostalCode(string $postalCode): self
  {
    $this->postalCode = $postalCode;

    return $this;
  }

  public function getPostalCode(): string
  {
    return $this->postalCode;
  }

  public function setComplement(string $complement): self
  {
    $this->complement = $complement;

    return $this;
  }

  public function getComplement(): string
  {
    return $this->complement;
  }

  private $latitude = null;
  private $longitude = null;
  private $provider = null;
  private $formatted = null;

  public function setLatitude($latitude): self
  {
    $this->latitude = $latitude === null || $latitude === '' ? null : (float) $latitude;
    return $this;
  }

  public function getLatitude()
  {
    return $this->latitude;
  }

  public function setLongitude($longitude): self
  {
    $this->longitude = $longitude === null || $longitude === '' ? null : (float) $longitude;
    return $this;
  }

  public function getLongitude()
  {
    return $this->longitude;
  }

  public function setProvider(?string $provider): self
  {
    $this->provider = $provider;
    return $this;
  }

  public function getProvider(): ?string
  {
    return $this->provider;
  }

  public function setFormatted(?string $formatted): self
  {
    $this->formatted = $formatted;
    return $this;
  }

  public function getFormatted(): ?string
  {
    return $this->formatted;
  }

  public function toArray(): array
  {
    return [
      'postalCode' => $this->getPostalCode(),
      'street' => $this->getStreet(),
      'number' => $this->getNumber(),
      'complement' => $this->getComplement(),
      'district' => $this->getDistrict(),
      'city' => $this->getCity(),
      'state' => $this->getState(),
      'uf' => $this->getUF(),
      'country' => $this->getCountry(),
      'latitude' => $this->getLatitude(),
      'longitude' => $this->getLongitude(),
      'provider' => $this->getProvider(),
      'formatted' => $this->getFormatted(),
    ];
  }
}
