<?php

namespace App\Library\Utils\Mautic\Client;

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
use Mautic\Api\Contacts as MauticApiContacts;

class Contacts
{

    private $auth     = null;

    private $endpoint = null;

    public function __construct(string $endpoint)
    {
      $this->endpoint = $endpoint;
    }

    public function setAuth(string $username, string $password): self
    {
      $auth = (new ApiAuth())->newAuth(['userName' => $username, 'password' => $password], 'BasicAuth');

      $this->auth = $auth;

      return $this;
    }

    public function getApi(): MauticApiContacts
    {
      if ($this->auth === null)
        throw new \Exception('Auth is not defined');

      return (new MauticApi())->newApi('contacts', $this->auth, $this->endpoint);
    }
}
