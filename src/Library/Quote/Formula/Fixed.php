<?php

namespace App\Library\Quote\Formula;

use App\Library\Quote\Core\AbstractFormula;
use App\Library\Quote\Core\DataBag;

class Fixed extends AbstractFormula
{
  public function getTotal(DataBag $tax)
  {
    return $tax->price > $tax->minimumPrice ? $tax->price : $tax->minimumPrice;
  }
}
