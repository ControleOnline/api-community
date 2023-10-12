<?php
namespace App\Library\Postalcode\Viacep;

use App\Library\Postalcode\PostalcodeProvider;
use App\Library\Postalcode\PostalcodeService;

class ViacepServiceProvider extends PostalcodeProvider
{
  public function __construct()
  {

  }

  public function getPostalcodeService(): PostalcodeService
  {
    return new ViacepService();
  }
}
