<?php

namespace App\Library\Quote\Formula;

use App\Library\Quote\Core\AbstractFormula;
use App\Library\Quote\Core\DataBag;

class FixedKg extends AbstractFormula
{

  public function getTotal(DataBag $tax)
  {
    $total = 0;

    $finalWeight = $this->calculateCubage($tax);


    if ($tax->isNull('region_destination_id')) {
      if ($tax->minimumPrice == 0)
        return 0;

      $value = $finalWeight / $tax->minimumPrice;
      $total = $value > 0 ? (ceil($value) * $tax->price) : 0;
    } else {
      $total = ($finalWeight - $tax->finalWeight) * $tax->price;
      $total = $total > $tax->minimumPrice ? $total : $tax->minimumPrice;
    }

    return $total;
  }

  private function calculateCubage(DataBag $tax)
  {

    $quote = $tax->parent()->params;
    $cubage = $tax->cubage > 0 ? $tax->cubage : 300;
    // calculate measures
    $maxCubage = 0;
    $totWeight = 0;

    if ($quote->hasPackages) {
      foreach ($quote->packages as $package) {
        $maxCubage += $package->qtd * $package->height * $package->width * $package->depth * $cubage;
        $totWeight += $package->qtd * $package->weight;
      }
    } else {
      $maxCubage = $quote->finalWeight + 0; // if packages is a numeric string transform to number      
    }

    return  $maxCubage > $totWeight ? $maxCubage : $totWeight;
  }
}
