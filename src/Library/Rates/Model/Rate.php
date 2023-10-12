<?php

namespace App\Library\Rates\Model;

class Rate
{
  /**
   * @var string
   */
  private $carrier = '';

  /**
   * @var string
   */
  private $carrierId = '';

  /**
   * @var string
   */
  private $table = '';

  /**
   * @var string
   */
  private $code = '';

  /**
   * @var float
   */
  private $price = 0;

  /**
   * @var int
   */
  private $deadline = 0;

  private $error = null;

  private $number;

  public function setCarrier($carrier)
  {
    $this->carrier = $carrier;

    return $this;
  }

  public function getCarrier()
  {
    return $this->carrier;
  }

  public function setCarrierId($carrierId)
  {
    $this->carrierId = $carrierId;

    return $this;
  }

  public function getCarrierId()
  {
    return $this->carrierId;
  }

  public function setCode($code)
  {
    $this->code = $code;

    return $this;
  }

  public function getCode()
  {
    return $this->code;
  }

  public function setPrice($price)
  {
    $this->price = $price;

    return $this;
  }

  public function getPrice()
  {
    return $this->price;
  }

  public function setDeadline($deadline)
  {
    $this->deadline = $deadline;

    return $this;
  }

  public function getDeadline()
  {
    return $this->deadline;
  }

  public function setError($error)
  {
    $this->error = $error;

    return $this;
  }

  public function getError()
  {
    return $this->error;
  }

  public function setTable($table)
  {
    $this->table = $table;

    return $this;
  }

  public function getTable()
  {
    return $this->table;
  }

  public function hasError(): bool
  {
    return !empty($this->error);
  }

  public function setNumber($number)
  {
    $this->number = $number;

    return $this;
  }

  public function getNumber()
  {
    return $this->number;
  }
}
