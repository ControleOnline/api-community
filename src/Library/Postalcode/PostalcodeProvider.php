<?php
namespace App\Library\Postalcode;

use App\Library\Postalcode\Entity\Address;

abstract class PostalcodeProvider
{
  abstract public function getPostalcodeService(): PostalcodeService;

  public function getAddress(string $postalCode): Address
  {
    return $this->getPostalcodeService()->query($postalCode);
  }
}
