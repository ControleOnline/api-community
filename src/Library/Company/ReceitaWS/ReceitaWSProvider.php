<?php

namespace App\Library\Company\ReceitaWS;


use App\Library\Company\CompanyProvider;
use App\Library\Company\CompanyService;


class ReceitaWSProvider extends CompanyProvider
{
  public function __construct()
  {
  }

  public function getCompanyService(): CompanyService
  {
    return new ReceitaWSService();
  }
}
