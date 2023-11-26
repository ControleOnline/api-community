<?php
namespace App\Library\Provider\Signature;

abstract class AbstractProvider implements SignatureFactory
{
  protected $config;

  public function __construct(?array $config = null)
  {
    $this->config = $config;
  }
}
