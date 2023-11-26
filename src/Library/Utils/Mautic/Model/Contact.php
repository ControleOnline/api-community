<?php

namespace App\Library\Utils\Mautic\Model;

class Contact
{

    private $email  = '';

    private $name   = '';

    private $phone  = '';

    private $tags   = [];

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(string $tagName): self
    {
      $this->tags[] = $tagName;

      return $this;
    }

    public function getTagList(): string
    {
      return implode(',', $this->tags);
    }
}
