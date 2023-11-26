<?php
namespace App\Library\Postalcode;

use App\Library\Postalcode\Entity\Address;

interface PostalcodeService
{
  public function query(string $postalCode): Address;
}
