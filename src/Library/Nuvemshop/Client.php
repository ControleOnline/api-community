<?php

namespace App\Library\Nuvemshop;

use GuzzleHttp\Client as GuzzClient;
use App\Library\Nuvemshop\Exception\ClientRequestException;
use App\Library\Nuvemshop\Model\User;
use App\Library\Nuvemshop\Model\Order;
use App\Library\Nuvemshop\Model\Customer;
use App\Library\Nuvemshop\Model\Address;
use App\Library\Nuvemshop\Model\Carrier;
use ControleOnline\Entity\People;

class Client
{

  private $user = null;

  public function __construct(User $user = null)
  {
    if ($user !== null) {
      $this->setUser($user);
    }
  }

  public function setUser(User $user)
  {
    $this->user = $user;
  }

  /**
   * Request NuvemShop authorization
   *
   * @param  string $code
   * @return User
   */
  public function createUser(string $code): User
  {
    try {

      $options  = [
        'json' => [
          'client_id'     => $_ENV['NUVEM_CLIENT_ID'],
          'client_secret' => $_ENV['NUVEM_CLIENT_SECRET'],
          'grant_type'    => 'authorization_code',
          'code'          => $code,
        ]
      ];
      $response = (new GuzzClient())
        ->post('https://www.tiendanube.com/apps/authorize/token', $options);

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error creating nuvemshop user');
        }

        if (isset($result->user_id) && isset($result->access_token)) {
          return (new User)
            ->setId   ($result->user_id)
            ->setToken($result->access_token);
        }
        else {
          if (isset($result->error)) {
            throw new \Exception($result->error_description);
          }
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  /**
   * Create shipping carrier
   *
   * @return int
   */
  public function createCarrier(): int
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $carrier  = new Carrier();
      $options  = [
        'json' => [
          'name'           => $carrier->getName(),
          'callback_url'   => $this->getCarrierRatesCallbackUrl(),
          'types'          => $carrier->getTypes(),
        ],
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->post(
          sprintf('https://api.nuvemshop.com.br/v1/%s/shipping_carriers', $this->user->getId()),
          $options
        );

      if ($response->getStatusCode() === 201) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error creating nuvemshop carrier');
        }

        if (isset($result->id)) {
          return $result->id;
        }
      }

      if ($response->getStatusCode() === 200) {
        if (isset($result->error)) {
          throw new \Exception($result->error_description);
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }
  
  private function fixCountryName(string $originalName): string
  {
    return strtolower($originalName) == 'brazil' ? 'Brasil' : $originalName;
  }

  /**
   * Update order to ready for pickup
   *
   * @return int
   */
  public function updateToReadyForPickup(int $orderId, string $reference, int $maxDays, People $company): int {

    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $date = new \DateTime('now');
      $origin = $company->getAddress()->first();
      $street   = $origin->getStreet();
      $district = $street->getDistrict();
      $city     = $district->getCity();
      $state    = $city->getState();
      $country  = $this->fixCountryName($state->getCountry()->getCountryName());

      $options  = [
        'json' => [
          'status'                => "ready_for_pickup",
          'description'           => "Pronto para retirada ($reference)",
          'city'                  => $city->getCity(),
          'province'              => $state->getState(),
          'country'               => $country,
          'happened_at'           => $date->format('Y-m-d\TH:i:d-0300'),
          'estimated_delivery_at' => $date->add(new \DateInterval('P'. $maxDays .'D'))
          ->format('Y-m-d\TH:i:d-0300')
        ],
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];

      $response = (new GuzzClient())
        ->post(
          sprintf('https://api.nuvemshop.com.br/v1/%s/orders/%d/fulfillments', $this->user->getId(), $orderId),
          $options
        );

      if ($response->getStatusCode() === 201) {

        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error creating nuvemshop carrier');
        }

        if (isset($result->id)) {
          return $result->id;
        }
      }

      if ($response->getStatusCode() === 200) {
        if (isset($result->error)) {
          throw new \Exception($result->error_description);
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );
    }
    catch (\Exception $e) {

      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  /**
   * Get carrier callback url string
   *
   * @return string
   */
  public function getCarrierRatesCallbackUrl(): string
  {
    if ($this->user === null) {
      throw new \Exception('Nuvemshop user is not defined');
    }

    return sprintf('https://%s/nuvem_shop/rates?api-key=%s', $this->user->getHost(), $this->user->getKey());
  }

  /**
   * Create shipping carrier options
   *
   * @param  string $code
   * @return array
   */
  public function createCarrierOptions(int $carrierId): bool
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $carrier  = new Carrier();
      $options  = [
        'json' => [
          'code'           => $carrier->getOptionCode(),
          'name'           => $carrier->getName(),
        ],
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->post(
          sprintf(
            'https://api.nuvemshop.com.br/v1/%s/shipping_carriers/%d/options',
            $this->user->getId(),
            $carrierId
          ),
          $options
        );

      if ($response->getStatusCode() === 201) {
        return true;
      }

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error creating nuvemshop carrier options');
        }

        if (isset($result->error)) {
          throw new \Exception($result->error_description);
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  /**
   * Create webhook
   *
   * @return int
   */
  public function createWebhook(string $event, string $callback): int
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $options  = [
        'json' => [
          'event' => $event,
          'url'   => sprintf(
            'https://%s/nuvem_shop/%s?api-key=%s',
            $this->user->getHost(), $callback, $this->user->getKey()),
        ],
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->post(
          sprintf('https://api.nuvemshop.com.br/v1/%s/webhooks', $this->user->getId()),
          $options
        );

      if ($response->getStatusCode() === 201) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error creating nuvemshop webhook');
        }

        if (isset($result->id)) {
          return $result->id;
        }
      }

      if ($response->getStatusCode() === 200) {
        if (isset($result->error)) {
          throw new \Exception($result->error_description);
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  /**
   * Get Order
   *
   * @return Order
   */
  public function getOrder(int $orderId): Order
  {

    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $options  = [
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->get(
          sprintf('https://api.nuvemshop.com.br/v1/%s/orders/%s', $this->user->getId(), $orderId),
          $options
        );

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error retrieving nuvemshop order');
        }

        if (isset($result->id)) {
          $order = (new Order)
            ->setId               ($result->id)
            ->setShippingOptionRef($result->shipping_option_reference)
            ->setShippingCostOwner($result->shipping_cost_owner)
            ->setShippingMaxDays  ($result->shipping_max_days)
          ;

          if (isset($result->customer)) {
            $customer = (new Customer())
              ->setId   ($result->customer->id)
              ->setName ($result->customer->name)
              ->setPhone($result->customer->phone)
              ->setEmail($result->customer->email)
              ->setIdentification($result->customer->identification)
            ;

            if (isset($result->customer->default_address)) {
              $address = (new Address())
                ->setId      ($result->customer->default_address->id)
                ->setAddress ($result->customer->default_address->address)
                ->setNumber  ($result->customer->default_address->number)
                ->setCity    ($result->customer->default_address->city)
                ->setZipcode ($result->customer->default_address->zipcode)
                ->setLocality($result->customer->default_address->locality)
                ->setProvince($result->customer->default_address->province)
              ;

              $customer->setAddress($address);
            }

            $order->setCustomer($customer);
          }

          if (isset($result->shipping_address)) {
            $address = (new Address())
              ->setId      ($result->shipping_address->id)
              ->setAddress ($result->shipping_address->address)
              ->setNumber  ($result->shipping_address->number)
              ->setCity    ($result->shipping_address->city)
              ->setZipcode ($result->shipping_address->zipcode)
              ->setLocality($result->shipping_address->locality)
              ->setProvince($result->shipping_address->province)
            ;

            $order->setShippingAddress($address);
          }

          return $order;
        }
        else {
          if (isset($result->error)) {
            throw new \Exception($result->error_description);
          }
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  /**
   * Get all carriers
   *
   * @return array
   */
  public function getAllCarriers(string $name = null): array
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $options  = [
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->get(
          sprintf('https://api.nuvemshop.com.br/v1/%s/shipping_carriers', $this->user->getId()),
          $options
        );

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          return [];
        }

        if (isset($result[0])) {
          $carriers = array_filter($result, function($c) use($name) {
            if ($name !== null) {
              return $c->name == $name;
            }
            return true;
          });

          $carriers = array_map(function($c) {
            return (new Carrier)
              ->setId  ($c->id)
              ->setName($c->name)
            ;
          }, $carriers);

          return $carriers;
        }
        else {
          if (isset($result->error)) {
            throw new \Exception($result->error_description);
          }
        }
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  public function findOneCarrierByName(string $name): ?Carrier
  {
    $carriers = $this->getAllCarriers($name);
    if (!isset($carriers[0])) {
      return null;
    }

    return $carriers[0];
  }

  /**
   * Update Carrier
   *
   * @return int
   */
  public function updateCarrier(int $carrierId, array $data): int
  {
    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $options  = [
        'json' => $data,
        'headers' => [
          'Content-Type'	 => 'application/json',
          'Authentication' => 'bearer '. $this->user->getToken()
        ]
      ];
      $response = (new GuzzClient())
        ->put(
          sprintf('https://api.nuvemshop.com.br/v1/%s/shipping_carriers/%s', $this->user->getId(), $carrierId),
          $options
        );

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if (empty($result)) {
          throw new \Exception('Error updating nuvemshop carrier');
        }

        if (isset($result->id)) {
          return $result->id;
        }
      }

      if (isset($result->error)) {
        throw new \Exception($result->error_description);
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {
      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        throw new ClientRequestException($contents->description);
      }

      throw new ClientRequestException($e->getMessage());
    }
  }
}
