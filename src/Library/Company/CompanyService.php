<?php
namespace App\Library\Company;

use App\Library\Company\Entity\Company;

interface CompanyService
{
  public function query(string $cnpj) :Company;
}
