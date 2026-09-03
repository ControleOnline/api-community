<?php

namespace App\Library\Nuvemshop\Model;

class Order
{
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $shipping_option_reference = null;

  /**
   * @var float
   */
  private $shipping_cost_owner = 0;

  /**
   * @var Customer
   */
  private $customer = null;

  /**
   * @var Address
   */
  private $shipping_address = null;

  /**
   * @var int
   */
  private $shipping_max_days = 0;

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setShippingOptionRef($reference)
  {
    $this->shipping_option_reference = $reference;

    return $this;
  }

  public function getShippingOptionRef()
  {
    return $this->shipping_option_reference;
  }

  public function getOrderReference(): ?string
  {
    if (empty($this->getShippingOptionRef())) {
      return null;
    }

    $ref = explode('-', $this->getShippingOptionRef());

    return $ref[0];
  }

  public function getQuoteReference(): ?string
  {
    if (empty($this->getShippingOptionRef())) {
      return null;
    }

    $ref = explode('-', $this->getShippingOptionRef());

    return isset($ref[1]) ? $ref[1] : null;
  }

  public function setShippingCostOwner($cost)
  {
    $this->shipping_cost_owner = $cost;

    return $this;
  }

  public function getShippingCostOwner()
  {
    return $this->shipping_cost_owner;
  }

  public function setCustomer(Customer $customer)
  {
    $this->customer = $customer;
  }

  public function getCustomer()
  {
    return $this->customer;
  }

  public function setShippingAddress(Address $address)
  {
    $this->shipping_address = $address;

    return $this;
  }

  public function getShippingAddress()
  {
    return $this->shipping_address;
  }

  public function setShippingMaxDays(int $maxDays)
  {
    $this->shipping_max_days = $maxDays;

    return $this;
  }

  public function getShippingMaxDays()
  {
    return $this->shipping_max_days;
  }
}
