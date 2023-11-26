<?php

namespace App\Library\Rates\Model;

class Quotation
{
  /**
   * @var string
   */
  private $origin = null;

  /**
   * @var string
   */
  private $destination = null;

  /**
   * @var array
   */
  private $products = [];

  /**
   * @var float
   */
  private $totalPrice = 0;

  public function setOrigin($origin)
  {
    $this->origin = $origin;

    return $this;
  }

  public function getOrigin()
  {
    return $this->origin;
  }

  public function setDestination($destination)
  {
    $this->destination = $destination;

    return $this;
  }

  public function getDestination()
  {
    return $this->destination;
  }

  public function addProduct(Product $product)
  {
    $this->products[] = $product;

    return $this;
  }

  public function getProducts(): array
  {
    return $this->products;
  }

  public function setTotalPrice($total)
  {
    $this->totalPrice = $total;

    return $this;
  }

  public function getTotalPrice()
  {
    return $this->totalPrice;
  }

  public function getTotalWeight()
  {
    $total = 0;

    foreach ($this->getProducts() as $product) {
      $total += $product->getWeight();
    }

    return $total;
  }
}
