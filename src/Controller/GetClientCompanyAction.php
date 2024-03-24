<?php

namespace App\Controller;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\Email;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class GetClientCompanyAction
{
  /*
   * @var Security
   */
  private $security;

  /**
   * Request
   *
   * @var Request
   */
  private $request  = null;

  /**
   * PeopleClient Repository
   *
   * @var \ControleOnline\Repository\PeopleClientRepository
   */
  private $clients  = null;

  /**
   * Current user
   *
   * @var \ControleOnline\Entity\User
   */
  private $currentUser = null;

  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  public function __construct(EntityManagerInterface $manager, Security $security)
  {
    $this->manager     = $manager;
    $this->currentUser = $security->getUser();
    $this->clients     = $this->manager->getRepository(PeopleClient::class);
  }

  public function __invoke(Request $request): JsonResponse
  {
    $this->request = $request;

    try {

      if (($people = $this->getPeopleCustomer()) === null) {
        throw new \Exception('Customer search Id was not defined');
      }

      return new JsonResponse([
        'response' => [
          'data'    => $this->getClientByPeople($people),
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ]);

    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getPeopleCustomer(): ?People
  {
      /*
      if ($this->clients->clientBelongsToOtherSalesman($document, $this->getMySalesCompany()))
        throw new \Exception('Este cliente já pertence a outro vendedor');
      */

      $document = $this->request->query->get('document', null);
      if (!empty($document)) {
        $document = $this->manager->getRepository(Document::class)
          ->findOneBy([
            'document' => $document,
          ]);

        if ($document === null || ($customer = $document->getPeople()) === null) {
          throw new \Exception('O cliente não foi encontrado');
        }

        return $customer;
      }

      $email = $this->request->query->get('email', null);
      if (!empty($email)) {
        $email = $this->manager->getRepository(Email::class)
          ->findOneBy([
            'email' => $email,
          ]);

        if ($email === null || ($customer = $email->getPeople()) === null) {
          throw new \Exception('O cliente não foi encontrado');
        }

        return $customer;
      }

      $name = $this->request->query->get('name', null);
      if (!empty($name)) {
        $people = $this->manager->getRepository(People::class)->createQueryBuilder('p')
            ->select()
            ->where  ('CONCAT(p.name, \' \', p.alias) = :name')
            ->orWhere('p.name = :name')
            ->orWhere('p.alias = :name')
            ->setParameters([
              'name' => $name
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (!isset($people[0])) {
          throw new \Exception('O cliente não foi encontrado');
        }

        return $people[0];
      }

      return null;
  }

  private function getMySalesCompany(): People
  {
    return ($this->currentUser->getPeople()->getPeopleCompany()->first())
      ->getCompany();
  }

  private function getClientByPeople(People $client): array
  {
    $documents = [];
    foreach ($client->getDocument() as $document) {
      $documents[] = [
        'document' => $document->getDocument(),
        'type'     => $document->getDocumentType()->getDocumentType(),
      ];
    }

    return [
        'id'          => $client->getId(),
        'documents'   => $documents,
        'name'        => $client->getName(),
        'alias'       => $client->getAlias(),
        'type'        => $client->getPeopleType(),
        'contact'     => $this->getClientMainContact($client),
        'address'     => $this->getClientAddress($client),
        'payment'     => $this->getClientPayment($client),
        'particulars' => $this->getClientParticulars($client),
        'birthday'    => $client->getFoundationDate() !== null ? $client->getFoundationDate()->format('Y-m-d') : null,
    ];
  }

  private function getClientParticulars(People $client): ?array
  {
    $particulars = [];

    $_particulars = $this->manager->getRepository(Particulars::class)
      ->findBy([
        'people' => $client
      ]);

    if (!empty($_particulars)) {
      /**
       * @var \ControleOnline\Entity\Particulars $particular
       */
      foreach ($_particulars as $particular) {
        $particulars[] = [
          'id'    => $particular->getId(),
          'type'  => [
            'id'    => $particular->getType()->getId(),
            'value' => $particular->getType()->getTypeValue(),
          ],
          'value' => $particular->getValue()
        ];
      }
    }

    return $particulars;
  }

  private function getClientPayment(People $client): ?array
  {
    return [
      'billing' => $client->getBilling(),
      'period'  => $client->getBillingDays(),
      'dueDay'  => $client->getPaymentTerm(),
    ];
  }

  private function getClientAddress(People $client): ?array
  {
    if (($address = $client->getAddress()->first()) === false)
      return null;

    $street   = $address->getStreet();
    $district = $street->getDistrict();
    $city     = $district->getCity();
    $state    = $city->getState();

    return [
      'id' => $address->getId(),
      'country'    => $this->fixCountryName($state->getCountry()->getCountryName()),
      'state'      => $state->getUF(),
      'city'       => $city->getCity(),
      'district'   => $district->getDistrict(),
      'postalCode' => $this->fixPostalCode($street->getCep()->getCep()),
      'street'     => $street->getStreet(),
      'number'     => $address->getNumber(),
      'complement' => $address->getComplement(),
    ];
  }

  private function getClientMainContact(People $client): ?array
  {
    $contact = null;

    if ($client->getPeopleType() == 'J') {
      if (!$client->getCompany()->isEmpty()) {
        $employee = $client->getCompany()->first();
        $contact  = $this->getContact($employee->getPeople());
      }
    }
    else {
      if ($client->getPeopleType() == 'F') {
        $contact  = $this->getContact($client);
      }
    }

    return $contact;
  }

  private function getContact(People $people): ?array
  {
    $email  = '';
    $code   = '';
    $number = '';

    if (($email = $people->getEmail()->first()) === false) {
      return null;
    }

    $phone = $people->getPhone()->first();

    return [
      'id'    => $people->getId(),
      'name'  => $people->getName(),
      'alias' => $people->getAlias(),
      'email' => $email->getEmail(),
      'phone' => $phone !== false ? sprintf('%s%s', $phone->getDdd(), $phone->getPhone()) : null,
    ];
  }

  private function fixCountryName(string $originalName): string
  {
    return strtolower($originalName) == 'brazil' ? 'Brasil' : $originalName;
  }

  private function fixPostalCode(int $postalCode): string
  {
    $code = (string)$postalCode;
    return strlen($code) == 7 ? '0' . $code : $code;
  }
}
