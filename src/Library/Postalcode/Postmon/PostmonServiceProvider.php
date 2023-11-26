<?php
namespace App\Library\Postalcode\Postmon;

use App\Library\Postalcode\PostalcodeProvider;
use App\Library\Postalcode\PostalcodeService;

class PostmonServiceProvider extends PostalcodeProvider
{
  public function __construct()
  {

  }

  public function getPostalcodeService(): PostalcodeService
  {
    return new PostmonService();
  }
}
