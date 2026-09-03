<?php
namespace App\Library\Company;

use App\Library\Company\Entity\Company;
use App\Library\Company\CompanyService;

abstract class CompanyProvider
{
  abstract public function getCompanyService(): CompanyService;

  public function getCnpj(string $postalCode): Company
  {
    return $this->getCompanyService()->query($postalCode);
  }
}
