<?php

namespace App\Library\Rates\Model;

class Product
{
  /**
   * @var float
   */
  private $width;

  /**
   * @var float
   */
  private $height;

  /**
   * @var float
   */
  private $depth;

  /**
   * @var float
   */
  private $weight;

  /**
   * @var int
   */
  private $quantity;

  public function setWidth($width)
  {
    $this->width = $width;

    return $this;
  }

  public function getWidth()
  {
    return $this->width;
  }

  public function setHeight($height)
  {
    $this->height = $height;

    return $this;
  }

  public function getHeight()
  {
    return $this->height;
  }

  public function setDepth($depth)
  {
    $this->depth = $depth;

    return $this;
  }

  public function getDepth()
  {
    return $this->depth;
  }

  public function setWeight($weight)
  {
    $this->weight = $weight;

    return $this;
  }

  public function getWeight()
  {
    return $this->weight;
  }

  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;

    return $this;
  }

  public function getQuantity()
  {
    return $this->quantity;
  }
}
