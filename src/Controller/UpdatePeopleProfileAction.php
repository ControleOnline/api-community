<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\User;
use ControleOnline\Entity\PeopleLink;
use App\Service\AddressService;
use App\Service\PeopleService;
use App\Service\PeopleRoleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UpdatePeopleProfileAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Address Service
   *
   * @var AddressService
   */
  private $address = null;

  /**
   * People Service
   *
   * @var PeopleService
   */
  private $people  = null;

  /**
   * Password encoder
   *
   * @var UserPasswordEncoderInterface
   */
  private $encoder = null;

  public function __construct(
    EntityManagerInterface       $entityManager,
    AddressService               $addressService,
    PeopleService                $peopleService,
    UserPasswordEncoderInterface $passwordEncoder,
    PeopleRoleService            $peopleRoleService)
  {
    $this->manager = $entityManager;
    $this->address = $addressService;
    $this->people  = $peopleService;
    $this->encoder = $passwordEncoder;
    $this->roles   = $peopleRoleService;
  }

  public function __invoke(People $data, Request $request, string $component): People
  {
    if ($content = $request->getContent()) {
      $params = json_decode($content, true);

      if (empty($params) || !is_array($params))
        throw new InvalidValueException('Content is empty');

      if (!isset($params['operation']) || !is_string($params['operation']))
        throw new InvalidValueException('Operation is not defined');

      if (!in_array($params['operation'], ['post', 'delete'], true))
        throw new InvalidValueException('Operation is not valid');

      if (!isset($params['payload']) || !is_array($params['payload']) || empty($params['payload']))
        throw new InvalidValueException('Payload is not defined');

      $handler = sprintf('handle%s', ucfirst($component));
      $this->$handler(
        $data, $params['payload'], $params['operation']
      );
    }

    return $data;
  }

  private function handlePhone(People $people, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':
        $phone = $this->manager->getRepository(Phone::class)->findOneBy(['ddd' => $payload['ddd'], 'phone' => $payload['phone']]);

        if ($phone instanceof Phone) {
          if ($phone->getPeople() instanceof People)
            throw new InvalidValueException('O telefone já esta em uso');

          if ($phone->getPeople() === null)
            $phone->setPeople($people);
        }
        else {
          $phone = new Phone();
          $phone->setDdd      ($payload['ddd']);
          $phone->setPhone    ($payload['phone']);
          $phone->setConfirmed(false);
          $phone->setPeople   ($people);
        }

        $this->manager->persist($phone);
      break;

      case 'delete':
        
        $phone = $this->manager->getRepository(Phone::class)->findOneBy(['id' => $payload['id'], 'people' => $people]);

        if (!$phone instanceof Phone)
          throw new ItemNotFoundException('Phone was not found');

        $this->manager->remove($phone);
      break;
    }
  }

  private function handleAddress(People $people, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':
        $address = $this->address->createFor($people, $payload);

        if ($address === null)
          throw new InvalidValueException('O endereço não foi criado');

        $this->manager->persist($address);
      break;

      case 'delete':
        
        $address = $this->manager->getRepository(Address::class)->findOneBy(['id' => $payload['id'], 'people' => $people]);

        if (!$address instanceof Address)
          throw new ItemNotFoundException('Address was not found');

        $address->setPeople(null);

        $this->manager->persist($address);
      break;
    }
  }

  private function handleEmail(People $people, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':
        $email = $this->manager->getRepository(Email::class)
          ->findOneBy([
            'email' => $payload['email'],
          ]);

        if ($email instanceof Email) {
          if ($email->getPeople() instanceof People)
            throw new InvalidValueException('O email já está em uso');

          if ($email->getPeople() === null)
            $email->setPeople($people);
        }
        else {
          $email = new Email();
          $email->setEmail    ($payload['email']);
          $email->setConfirmed(false);
          $email->setPeople   ($people);
        }

        $this->manager->persist($email);
      break;

      case 'delete':
        
        $email = $this->manager->getRepository(Email::class)->findOneBy(['id' => $payload['id'], 'people' => $people]);

        if (!$email instanceof Email)
          throw new ItemNotFoundException('Email was not found');

        $this->manager->remove($email);
      break;
    }
  }

  private function handleUser(People $people, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':
        $user = $this->manager->getRepository(User::class)
          ->findOneBy([
            'username' => $payload['username'],
          ]);

        if ($user instanceof User)
          throw new InvalidValueException('O username já esta em uso');

        $user = new User();
        $user->setUsername($payload['username']);
        $user->setHash    ($this->encoder->encodePassword($user, $payload['password']));
        $user->setPeople  ($people);

        $this->manager->persist($user);
      break;

      case 'delete':
        $users = $this->manager->getRepository(User::class)->findBy(['people' => $people]);
        if (count($users) == 1)
          throw new InvalidValueException('Deve existir pelo menos um usuário');

        $user = $this->manager->getRepository(User::class)->findOneBy(['id' => $payload['id'], 'people' => $people]);

        if (!$user instanceof User)
          throw new ItemNotFoundException('User was not found');

        $this->manager->remove($user);
      break;
    }
  }

  private function handleDocument(People $people, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':
        $doctype  = $this->manager->getRepository(DocumentType::class)->find($payload['type']);

        if ($doctype === null)
          throw new ItemNotFoundException('Document type not found');

        $document = $this->manager->getRepository(Document::class)
          ->findOneBy([
            'document'     => $payload['document'],
            'documentType' => $doctype,
          ]);

        if ($document instanceof Document)
          throw new InvalidValueException('O documento já está em uso');

        $document = new Document();
        $document->setDocument    ($payload['document']);
        $document->setDocumentType($doctype);
        $document->setPeople      ($people);

        $this->manager->persist($document);
      break;

      case 'delete':        
        $document = $this->manager->getRepository(Document::class)->findOneBy(['id' => $payload['id'], 'people' => $people]);

        if (!$document instanceof Document)
          throw new ItemNotFoundException('Document was not found');

        $this->manager->remove($document);
      break;
    }
  }

  /**
   * Esta funcao foi adaptada para manter compatibilidade.
   * @todo usar AdminPeopleEmployeesAction
   */
  private function handleEmployee(People $company, array $payload, string $operation)
  {
    switch ($operation) {
      case 'post':

        try {
          $this->manager->getConnection()->beginTransaction();

          $employee = $this->people->create($payload, false);

          $contract = new PeopleLink();
          $contract->setCompany ($company);
          $contract->setPeople($employee);
          $contract->setEnabled (true);

          $this->manager->persist($contract);

          $this->manager->flush();
          $this->manager->getConnection()->commit();

        } catch (\Exception $e) {
          if ($this->manager->getConnection()->isTransactionActive())
              $this->manager->getConnection()->rollBack();

          throw new \Exception($e->getMessage());
        }

      break;

      case 'delete':
        if ($company->getId() == $payload['id'])
          throw new InvalidValueException('Can not delete your own people employee');

        $employee = $this->manager->getRepository(People::class)->find($payload['id']);
        if ($employee === null)
          throw new ItemNotFoundException('Employee not found');

        $contract = $this->manager->getRepository(PeopleLink::class)
          ->findOneBy([
            'employee' => $employee,
            'company'  => $company
          ]);

        if ($contract === null)
          throw new ItemNotFoundException('Company employee relationship not found');

        $this->manager->remove($contract);

      break;
    }
  }
}
