<?php

namespace App\Library\Nuvemshop\Model;

class Carrier
{
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $name = 'Controle Online';

  /**
   * @var string
   */
  private $types = 'ship,pickup';

  /**
   * @var bool
   */
  private $active = true;

  /**
   * @var string
   */
  private $optionCode = 'frck';

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  public function getTypes()
  {
    return $this->types;
  }

  public function setTypes($types)
  {
    $this->types = $types;

    return $this;
  }

  public function getActive()
  {
    return $this->active;
  }

  public function setActive($active)
  {
    $this->active = $active;

    return $this;
  }

  public function getOptionCode()
  {
    return $this->optionCode;
  }
}
