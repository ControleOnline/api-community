<?php

namespace App\Library\Rates\Correios;

use FlyingLuscas\Correios\Client as CorreiosClient;
use FlyingLuscas\Correios\Service;

use App\Library\Rates\Exception\ClientRequestException;
use App\Library\Rates\CarrierRatesInterface;
use App\Library\Rates\Model\User;
use App\Library\Rates\Model\Quotation;
use App\Library\Rates\Model\Rate;

class Client implements CarrierRatesInterface
{

  private $user = null;

  public function setUser(User $user)
  {
    $this->user = $user;
  }

  /**
   *
   * @return int
   */
  public function getRates(Quotation $quotation): array
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $correios = new CorreiosClient;

      $freight = $correios->freight();

      $freight
        ->origin       ($quotation->getOrigin())
        ->destination  ($quotation->getDestination())
        ->services     (Service::SEDEX, Service::PAC)
        ->useOwnHand   (false)
        ->declaredValue($quotation->getTotalPrice())
        ->credentials  ($this->user->getKey(), $this->user->getToken())
      ;

      foreach ($quotation->getProducts() as $product) {
        $freight->item(
          (((float) $product->getWidth ()) * 100),
          (((float) $product->getHeight()) * 100),
          (((float) $product->getDepth ()) * 100),
          $product->getWeight(),
          $product->getQuantity()
        );
      }

      $result = $freight->calculate();

      if (empty($result)) {
        return [];
      }

      $rates  = [];
      $number = 1;

      foreach ($result as $rate) {
        $rates[] = (new Rate)
          ->setCarrier ('Correios')
          ->setTable   ($rate['name' ])
          ->setCode    ($rate['code' ])
          ->setPrice   ($rate['price'])
          ->setDeadline($rate['deadline'])
          ->setNumber  ($number)
          ->setError   ($rate['error']);

        $number++;
      }

      return $rates;

    } catch (\Exception $e) {
      throw new ClientRequestException($e->getMessage());
    }
  }
}
