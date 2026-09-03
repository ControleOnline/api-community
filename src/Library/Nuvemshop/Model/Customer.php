<?php

namespace App\Library\Nuvemshop\Model;

class Customer
{
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $phone;

  /**
   * @var string
   */
  private $email;

  /**
   * @var string
   */
  private $identification;

  /**
   * @var Address
   */
  private $address = null;

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setAddress(Address $address)
  {
    $this->address = $address;

    return $this;
  }

  public function getAddress()
  {
    return $this->address;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  public function getPhone()
  {
    return $this->phone;
  }

  public function setPhone($phone)
  {
    $this->phone = $phone;

    return $this;
  }

  public function getEmail()
  {
    return $this->email;
  }

  public function setEmail($email)
  {
    $this->email = $email;

    return $this;
  }

  public function getIdentification()
  {
    return $this->identification;
  }

  public function setIdentification($identification)
  {
    $this->identification = $identification;

    return $this;
  }
}
