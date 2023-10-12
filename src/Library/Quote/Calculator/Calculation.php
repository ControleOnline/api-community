<?php

namespace App\Library\Quote\Calculator;

class Calculation
{
  private $result = null;

  public function sum($value)
  {
    if (is_numeric($value) && !is_string($value)) {
      if ($this->result === null)
        $this->result = 0;

      $this->result += $value;
    }
  }

  public function isEmpty(): bool
  {
    return $this->result === null ? true : false;
  }

  public function result()
  {
    return $this->result;
  }

  public function reset()
  {
    $this->result = null;
  }
}
