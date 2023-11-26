<?php

namespace App\Library\Nuvemshop\Model;

class Address
{
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $address;

  /**
   * @var string
   */
  private $number;

  /**
   * @var string
   */
  private $city;

  /**
   * @var string
   */
  private $province;

  /**
   * @var string
   */
  private $locality;

  /**
   * @var string
   */
  private $zipcode;

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getAddress()
  {
    return $this->address;
  }

  public function setAddress($address)
  {
    $this->address = $address;

    return $this;
  }

  public function getNumber()
  {
    return $this->number;
  }

  public function setNumber($number)
  {
    $this->number = $number;

    return $this;
  }

  public function getCity()
  {
    return $this->city;
  }

  public function setCity($city)
  {
    $this->city = $city;

    return $this;
  }

  public function getProvince()
  {
    return $this->province;
  }

  public function setProvince($province)
  {
    $this->province = $province;

    return $this;
  }

  public function getLocality()
  {
    return $this->locality;
  }

  public function setLocality($locality)
  {
    $this->locality = $locality;

    return $this;
  }

  public function getZipcode()
  {
    return $this->zipcode;
  }

  public function setZipcode($zipcode)
  {
    $this->zipcode = $zipcode;

    return $this;
  }
}
