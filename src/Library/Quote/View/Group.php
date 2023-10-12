<?php

namespace App\Library\Quote\View;

use App\Library\Quote\Exception\ExceptionInterface;
use App\Library\Quote\Exception\ClassNotFoundException;
use App\Library\Quote\Exception\InvalidArgumentException;
use App\Library\Quote\Exception\ResultIsNullException;

use App\Library\Quote\Core\DataBag;
use App\Library\Quote\Calculator\Calculator;

class Group
{
  private $data = null;

  public function __construct(DataBag $data)
  {
    $this->data = $data;
  }

  public function getResults(): ?array
  {
    try {

      $calc  = new Calculator();
      $taxes = $this->groupTaxes();
      $gpTot = 0;

      // INDEPENDENT TAXES
      foreach ($taxes['indps'] as $tax) {
        $txTot = $calc->getTaxTotal($tax);

        if ($tax->type == 'fixed' && $tax->subType == 'km') {
          if ($txTot === null)
            throw new ResultIsNullException('Tax result is null');
        }

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      $gpTot = $calc->getCalculation('total')->result();

      // PERCENTUAL TAXES
      foreach ($taxes['perce'] as $tax) {
        $txTot = ($gpTot / 100) * $tax->price;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      $gpTot = $calc->getCalculation('total')->result();


      // ROYALTY
      foreach ($taxes['ryt'] as $tax) {
        $txTot = ($gpTot / ((100 - $tax->price) / 100)) - $gpTot;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      // MKT
      foreach ($taxes['mkt'] as $tax) {
        $txTot = ($gpTot / ((100 - $tax->price) / 100)) - $gpTot;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }


      $gpTot = $calc->getCalculation('total')->result();


      // IMPOSTO

      foreach ($taxes['impt'] as $tax) {
        $txTot = ($gpTot / ((100 - $tax->price) / 100)) - $gpTot;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      $gpTot = $calc->getCalculation('total')->result();


      // ICMS
      if ($this->data->carrier->icms === true) {
        foreach ($taxes['icms'] as $tax) {
          $txTot = ($gpTot / ((100 - $tax->price) / 100)) - $gpTot;
          $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

          $calc->getCalculation($tax->name)->sum($txTot);
          $calc->getCalculation('total')->sum($txTot);
        }

        $gpTot = $calc->getCalculation('total')->result();
      }

      // Conveniência
      foreach ($taxes['conv'] as $tax) {
        $txTot = ($gpTot / ((100 - $tax->price) / 100)) - $gpTot;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      $gpTot = $calc->getCalculation('total')->result();


      // TOTAL TAXES
      foreach ($taxes['total'] as $tax) {
        $txTot = ($gpTot / 100) * $tax->price;
        $txTot = $txTot > $tax->minimumPrice ? $txTot : $tax->minimumPrice;

        $calc->getCalculation($tax->name)->sum($txTot);
        $calc->getCalculation('total')->sum($txTot);
      }

      return $calc->getCalculationResults();
    } catch (\Exception $e) {
      if (!$e instanceof ExceptionInterface) {
        throw new \Exception($e->getMessage());
      }

      return null;
    }
  }

  private function groupTaxes(): array
  {
    $taxes = [
      'conv'  => [],
      'indps' => [],
      'perce' => [],
      'icms'  => [],
      'total' => [],
      'ryt'   => [],
      'impt'  => [],
      'mkt'   => []
    ];

    foreach ($this->data->taxes as $tax) {
      if ($tax->type == 'percentage' && $tax->subType == 'order') {
        if (in_array(preg_replace('/\s+/', ' ', trim($tax->name)),['TAXA DE CONVENIENCIA', 'TAXA DE CONVENIÊNCIA', 'CONVENIÊNCIA', 'CONVENIENCIA'])) {
          $taxes['conv'][] = $tax;
        } elseif (in_array($tax->name, ['ICMS'])) {
          $taxes['icms'][]  = $tax;
        } elseif (in_array($tax->name, ['IMPOSTO'])) {
          $taxes['impt'][]  = $tax;
        } elseif (in_array($tax->name, ['ROYALTY',])) {
          $taxes['ryt'][]  = $tax;
        } elseif (in_array($tax->name, ['MARKETING'])) {
          $taxes['mkt'][]  = $tax;
        } else {
          $taxes['perce'][] = $tax;
        }
      } else {
        $taxes['indps'][] = $tax;
      }
    }

    return $taxes;
  }
}
