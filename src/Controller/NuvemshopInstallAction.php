<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Library\Nuvemshop\Client;
use App\Library\Nuvemshop\Model\Carrier as NuvemCarrier;
use ControleOnline\Entity\User;

class NuvemshopInstallAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request = null;

    /**
     * Nuvemshop client
     *
     * @var Client
     */
    private $client  = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $user = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
      $this->manager = $entityManager;
      $this->client  = new Client;
      $this->user    = $security->getUser();
    }

    public function __invoke(Request $request, string $code): JsonResponse
    {
      try {

        // get user authorization

        $this->manager->getConnection()->beginTransaction();

        $user = $this->client->createUser($code);

        $this->user->setOauthUser($user->getId());
        $this->user->setOauthHash($user->getToken());

        $this->manager->persist($this->user);

        $this->manager->flush();
        $this->manager->getConnection()->commit();

        // update nuvemshop user

        $user->setHost($_SERVER['HTTP_HOST']);
        $user->setKey ($this->user->getApiKey());

        $this->client->setUser($user);

        // create or update shipping carrier

        $default = new NuvemCarrier;
        $carrier = $this->client->findOneCarrierByName($default->getName());

        //// if carrier already exist

        if ($carrier instanceof NuvemCarrier) {

          // update carrier data

          $payload = [
            'callback_url' => $this->client->getCarrierRatesCallbackUrl(),
            'active'       => $default->getActive(),
            'types'        => $default->getTypes(),
          ];

          $this->client->updateCarrier($carrier->getId(), $payload);
        }

        //// if carrier doesnt exist

        else {
          $carrierId = $this->client->createCarrier();

          // create carrier options

          $this->client->createCarrierOptions($carrierId);

          // create order hooks

          //// when order/paid
          $this->client->createWebhook('order/paid', 'order-paid');
        }

        return new JsonResponse([
          'response' => [
            'data'    => [
              'storeId' => $user->getId()
            ],
            'error'   => '',
            'success' => true,
          ],
        ], 200);

      } catch (\Exception $e) {
        if ($this->manager->getConnection()->isTransactionActive()) {
          $this->manager->getConnection()->rollBack();
        }

        return new JsonResponse([
          'response' => [
            'data'    => null,
            'error'   => $e->getMessage(),
            'success' => false,
          ],
        ]);
      }
    }
}
