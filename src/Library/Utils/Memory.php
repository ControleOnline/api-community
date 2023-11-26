<?php

namespace App\Library\Utils;

class Memory
{
    protected $objects = [];

    public function __construct()
    {

    }

    public function add(string $id, $object)
    {
        $this->objects[$id] = $object;
    }

    public function get(string $id)
    {
        return \array_key_exists($id, $this->objects) ? $this->objects[$id] : null;
    }

    public function __get($id)
    {
      return $this->get($id);
    }
}
