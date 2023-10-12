<?php

namespace App\Library\Utils\Mautic\Model;

class Company
{

    private $alias = '';

    private $name  = '';

    public function setAlias(string $alias): self
    {
      $this->alias = $alias;

      return $this;
    }

    public function setName(string $name): self
    {
      $this->name = $name;

      return $this;
    }

    public function getAlias(): string
    {
      return $this->alias;
    }

    public function getName(): string
    {
      return $this->name;
    }
}
