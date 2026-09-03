<?php

namespace App\Library\Quote\Formula;

use App\Library\Utils\GMaps;
use App\Library\Quote\Core\AbstractFormula;
use App\Library\Quote\Core\DataBag;

class FixedKm extends AbstractFormula
{
  public function getTotal(DataBag $tax)
  {
    $total = 0;

    if (($distd = $this->getDistanceDelivery($tax)) === null)
      return null;

    $total = $distd / $tax->price;

    return $total > $tax->minimumPrice ? $total : $tax->minimumPrice;
  }

  /**
   * Returns distance in km
   */
  private function getDistanceDelivery(DataBag $tax)
  {
    if (isset($_ENV['GMAPS_KEY'])) {
      GMaps::setKey($_ENV['GMAPS_KEY']);

      $oAddress = $tax->parent()->params->cityOriginName      . ' - ' . $tax->parent()->params->stateOriginName      . ' - ' . $tax->parent()->params->countryOriginName;
      $dAddress = $tax->parent()->params->cityDestinationName . ' - ' . $tax->parent()->params->stateDestinationName . ' - ' . $tax->parent()->params->countryDestinationName;

      $distance = GMaps::distanceMatrix($oAddress, $dAddress);

      if (is_numeric($distance))
        return $distance / 1000;
    }

    return null;
  }
}
