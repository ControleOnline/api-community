<?php

namespace App\Library\Quote\Core;

abstract class AbstractFormula
{
  private static $instances = [];

  protected function __construct()
  {

  }

  protected function __clone()
  {

  }

  public function __wakeup()
  {
      throw new \Exception(
        sprintf('Cannot unserialize "%s"', static::class)
      );
  }

  public static function getInstance()
  {
      $subclass = static::class;
      if (!isset(self::$instances[$subclass])) {
          self::$instances[$subclass] = new static;
      }
      return self::$instances[$subclass];
  }

  abstract public function getTotal(DataBag $tax);
}
