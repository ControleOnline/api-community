<?php
namespace App\Library\Postalcode\GoogleMaps;

use App\Library\Postalcode\PostalcodeProvider;
use App\Library\Postalcode\PostalcodeService;

class GoogleMapsServiceProvider extends PostalcodeProvider
{
  public function __construct()
  {
  }

  public function getPostalcodeService(): PostalcodeService
  {
    return new GoogleMapsService();
  }
}
