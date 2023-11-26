<?php

namespace App\Entity;

class Order
{
  public function isOriginAndDestinationTheSame(): ?bool
  {
    if (($origin = $this->getAddressOrigin()) === null) {
      return null;
    }

    if (($destination = $this->getAddressDestination()) === null) {
      return null;
    }

    $origCity = $origin->getStreet()->getDistrict()->getCity();
    $destCity = $destination->getStreet()->getDistrict()->getCity();

    // both objects are the same entity ( = same name and same state)

    if ($origCity === $destCity) {
      return true;
    }

    return false;
  }

  public function isOriginAndDestinationTheSameState(): ?bool 
  {
    if (($origin = $this->getAddressOrigin()) === null) {
      return null;
    }

    if (($destination = $this->getAddressDestination()) === null) {
      return null;
    }

    $origState = $origin->getStreet()->getDistrict()->getCity()->getState();
    $destState = $destination->getStreet()->getDistrict()->getCity()->getState();

    // both objects are the same entity ( = same name and same country)

    if ($origState === $destState) {
      return true;
    }

    return false;
  }
}
