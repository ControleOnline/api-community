<?php

namespace App\Library\Quote\Core;

use App\Library\Quote\Exception\InvalidRequestException;
use App\Library\Quote\Exception\InvalidArgumentException;
use App\Library\Quote\Exception\PropertyNotFoundException;

class DataBag
{
  private $values = [];

  private $parent =  null;

  public function __construct(array $values, DataBag $parent = null)
  {
    $this->parent = $parent;

    $this->setValues($values);
  }

  public function __clone()
  {
    foreach ($this->values as $key => $value) {
      unset($this->values[$key]);
    }
  }

  public function __set($name, $value)
  {
    throw new InvalidRequestException(
      sprintf('Can not set values in %s', static::class)
    );
  }

  public function __get($key)
  {


    if (!array_key_exists($key, $this->values))
      throw new PropertyNotFoundException(
        sprintf('Property bag "%s" not found in "%s".', $key, 
        //static::class 
        json_encode($this->values)
        )
      );

    return $this->values[$key];
  }

  public function parent(): ?DataBag
  {
    return $this->parent;
  }

  public function valueExists(string $valueName): bool
  {
    return array_key_exists($valueName, $this->values);
  }

  public function isNull(string $valueName): bool
  {
    return !$this->valueExists($valueName) ? true : is_null($this->values[$valueName]);
  }

  protected function setValues(array $values)
  {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $first = current($value);

        if (is_array($first)) {
          $subBags = [];
          foreach ($value as $val) {
            $subBags[] = new DataBag($val, $this);
          }

          $this->values[$key] = $subBags;
          continue;
        }

        $this->values[$key] = new DataBag($value, $this);

        continue;
      }

      $this->values[$key] = $value;
    }
  }
}
