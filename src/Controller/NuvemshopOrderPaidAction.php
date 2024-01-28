<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Library\Nuvemshop\Client;
use App\Library\Nuvemshop\Model\User     as NuvemUser;
use App\Library\Nuvemshop\Model\Order    as NuvemOrder;
use App\Library\Nuvemshop\Model\Customer as NuvemCustomer;
use App\Library\Nuvemshop\Model\Address  as NuvemAddress;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Order;
use App\Service\PeopleService;
use App\Service\AddressService;

/**
 * Class NuvemShopAction
 * @package App\Controller
 * @Route("/nuvem_shop")
 */
class NuvemshopOrderPaidAction extends AbstractController
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

    public function __construct(EntityManagerInterface $entityManager, Security $security, PeopleService $people, AddressService $address)
    {
      $this->manager  = $entityManager;
      $this->client   = new Client;
      $this->security = $security;
      $this->people   = $people;
      $this->address  = $address;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/order-paid", methods={"POST"})
     */
    public function orderPaid(Request $request): JsonResponse
    {
      try {

        // verify api key

        $user = $this->manager->getRepository(User::class)
          ->findOneBy(['apiKey' => $request->query->get('api-key')]);
        if ($user === null) {
          throw new \Exception('Access denied');
        }
        else {

          // auto log

          $this->get('security.token_storage')
            ->setToken(
              new UsernamePasswordToken($user, null, 'main', $user->getRoles())
            );
        }

        // set nuvemshop user

        $nuser = (new NuvemUser())
          ->setId   ($this->security->getToken()->getUser()->getOauthUser())
          ->setToken($this->security->getToken()->getUser()->getOauthHash())
          ->setKey  ($this->security->getToken()->getUser()->getApiKey())
          ->setHost ($_SERVER['HTTP_HOST'])
        ;

        $this->client->setUser($nuser);

        // handle nuvemshop order

        $data  = json_decode($request->getContent(), true);
        if (!isset($data['id'])) {
          throw new \Exception('Order id is not defined');
        }

        $order = $this->client->getOrder($data['id']);

        // update freteclick order

        $result = $this->updateOrder($order);

        return new JsonResponse([
          'response' => [
            'data'    => [
              'id' => $result->getId()
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

    private function updateOrder(NuvemOrder $nuvemOrder): Order
    {

      $orderReference = $nuvemOrder->getOrderReference();
      $quoteReference = $nuvemOrder->getQuoteReference();

      $order = $this->manager->getRepository(Order::class)
        ->find($orderReference);

      if ($order === null) {
        throw new \Exception('Purchasing order not found');
      }

      $company = $this->getMyCompany();
      if ($company === null) {
        throw new \Exception('Company was not found');
      }

      $caddress = $this->getPeopleAddress($company);
      if ($caddress === null) {
        throw new \Exception('Company address was not found');
      }
      else {
        $caddress = $caddress->getId();
      }

      if ($nuvemOrder->getCustomer() === null) {
        throw new \Exception('Store customer was not found');
      }
      if ($nuvemOrder->getCustomer()->getAddress() === null) {
        throw new \Exception('Store customer address was not found');
      }

      $customer = $this->getCustomer($nuvemOrder->getCustomer());
      if ($customer === null) {
        throw new \Exception('Customer was not found');
      }

      $saddress = $this->getPeopleAddress($customer, $nuvemOrder->getShippingAddress());
      if ($saddress === null) {
        $saddress = [
          'country'     => 'Brasil',
          'state'       => $nuvemOrder->getShippingAddress()->getProvince(),
          'city'        => $nuvemOrder->getShippingAddress()->getCity(),
          'district'    => $nuvemOrder->getShippingAddress()->getLocality(),
          'street'      => $nuvemOrder->getShippingAddress()->getAddress(),
          'postal_code' => $nuvemOrder->getShippingAddress()->getZipcode(),
          'number'      => $nuvemOrder->getShippingAddress()->getNumber(),
        ];
      }
      else {
        $saddress = $saddress->getId();
      }

      $payload = [
        'quote'    => $quoteReference,
        'price'    => $nuvemOrder->getShippingCostOwner(),
        'payer'    => $company->getId(),
        'retrieve' => [
          'id'      => $company->getId(),
          'address' => $caddress,
          'contact' => $this->security->getToken()->getUser()->getPeople()->getId(),
        ],
        'delivery' => [
          'id'      => $customer->getId(),
          'address' => $saddress,
          'contact' => $customer->getId(),
        ],
      ];
      $action = 'App\Controller\ChooseQuoteAction::__invoke';
      $empty  = [];
      $params = [
        'data'    => $order,
        'request' => new Request($empty, $empty, $empty, $empty, $empty, $empty, json_encode($payload)
        )
      ];

      $response = $this->forward($action, $params);

      if ($response->getStatusCode() !== 200) {
        throw new \Exception('An unexpected error occurred while updating the order');
      }
      else {
        $contents = json_decode($response->getContent());
        
        if ($contents !== null && !isset($contents->{'@id'})) {
          throw new \Exception($contents->response->error);
        }
      }

      return $order;
    }

    private function getMyCompany(): ?People
    {
      $companies = $this->security->getToken()->getUser()->getPeople()->getPeopleCompany();

      if ($companies->first() === false)
          return null;

      return $companies->first()->getCompany();
    }

    private function getPeopleAddress(People $people, NuvemAddress $naddress = null): ?Address
    {
      $address = null;

      if ($naddress === null) {
        if (($address = $people->getAddress()->first()) === false) {
          return null;
        }
      }
      else {
        return $this->manager->getRepository(Address::class)
          ->findPeopleAddressBy(
            $people,
            [
              'country'  => 'Brazil',
              'state'    => $naddress->getProvince(),
              'city'     => $naddress->getCity(),
              'district' => $naddress->getLocality(),
              'street'   => $naddress->getAddress(),
              'number'   => $naddress->getNumber(),
            ]
          );
      }

      return $address;
    }

    private function getCustomer(NuvemCustomer $nuvemCustomer): ?People
    {
      $identification = $nuvemCustomer->getIdentification();

      $people = null;
      $email = null;
      $document = null;

      if ($identification) {
        $document = $this->manager->getRepository(Document::class)
          ->findOneBy([
            'document' => $identification
          ]);
      }
      
      if ($document === null) {

        $email = $this->manager->getRepository(Email::class)
          ->findOneBy([
            'email' => $nuvemCustomer->getEmail()
          ]);

        if ($email !== null) {
          $people = $email->getPeople();

          // update people customer
          $documents[] = [
            "document" => $identification,
            "type" => $this->people->getPeopleDocumentTypeByDoc($identification)->getId()
          ];

          $params = [
            'name'  => $nuvemCustomer->getName(),
            'alias' => $nuvemCustomer->getName(),
            'email' => $nuvemCustomer->getEmail(),
            'documents' => $documents
          ];

          $people = $this->people->update($people->getId(), $params, false);

        }

      }
      else {
        $people = $document->getPeople();
      }

      if ($email === null && $document === null) {
        $this->manager->getConnection()->beginTransaction();

        // create people customer

        $documents[] = [
          "document" => $identification,
          "type" => $this->people->getPeopleDocumentTypeByDoc($identification)->getId()
        ];

        $params = [
          'name'  => $nuvemCustomer->getName(),
          'alias' => $nuvemCustomer->getName(),
          'type'  => 'F',
          'email' => $nuvemCustomer->getEmail(),
          'documents' => $documents
        ];

        $people = $this->people->create($params, false);

        // create customer address

        $params = [
          'country'     => 'Brasil',
          'state'       => $nuvemCustomer->getAddress()->getProvince(),
          'city'        => $nuvemCustomer->getAddress()->getCity(),
          'district'    => $nuvemCustomer->getAddress()->getLocality(),
          'postal_code' => $nuvemCustomer->getAddress()->getZipCode(),
          'street'      => $nuvemCustomer->getAddress()->getAddress(),
          'number'      => $nuvemCustomer->getAddress()->getNumber(),
        ];
        $address = $this->address->createFor($people, $params);

        $this->manager->flush();
        $this->manager->getConnection()->commit();

        // add address to customer

        $people->getAddress()->add($address);
        
        return $people;
      }
      else {
        return $people;
      }
    }
}
