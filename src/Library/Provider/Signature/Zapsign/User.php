<?php
namespace App\Library\Provider\Signature\Zapsign;

class User
{
  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $token;

  /**
   * @var string
   */
  private $key;

  /**
   * @var string
   */
  private $host;

  /**
   * @var bool
   */
  private $isSandbox = false;

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setIsSandbox(bool $isSandbox)
  {
    $this->isSandbox = $isSandbox;

    return $this;
  }

  public function isSandbox(): bool
  {
    return $this->isSandbox;
  }

  public function setToken($token)
  {
    $this->token = $token;

    return $this;
  }

  public function getToken()
  {
    return $this->token;
  }

  public function setKey($key)
  {
    $this->key = $key;

    return $this;
  }

  public function getKey()
  {
    return $this->key;
  }

  public function setHost($host)
  {
    $this->host = $host;

    return $this;
  }

  public function getHost()
  {
    return $this->host;
  }
}
