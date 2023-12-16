<?php

namespace App\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PeopleService
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Encoder
   *
   * @var UserPasswordEncoderInterface
   */
  private $encoder = null;

  public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
  {
    $this->manager = $entityManager;
    $this->encoder = $passwordEncoder;
  }

  public function createDocument(array $data, People $people): void
  {

    if (isset($data['documents']) && is_array($data['documents'])) {
      foreach ($data['documents'] as $doc) {
        if (empty($doc['document']))
          continue;

        $document = $this->documentExist($doc['document'], $doc['type']);
        if ($document instanceof Document) {
          if ($document->getPeople() != $people)
            throw new \InvalidArgumentException(
              sprintf('O documento %s já esta em uso', $doc['document']),
              100
            );
        } else {
          $document = new Document();
          $document->setDocument($doc['document']);
          $document->setDocumentType($this->getPeopleDocumentType($doc['type']));
          $document->setPeople($people);

          $this->manager->persist($document);
        }
      }
    }
  }

  public function createEmail(array $data, People $people): void
  {

    if (isset($data['email']) && is_string($data['email'])) {
      $email = $this->manager->getRepository(Email::class)
        ->findOneBy([
          'email' => $data['email'],
        ]);

      if ($email instanceof Email) {
        if ($email->getPeople() instanceof People && $email->getPeople() != $people)
          throw new \InvalidArgumentException('O email já está em uso', 102);

        if ($email->getPeople() === null)
          $email->setPeople($people);
      } else {
        $email = new Email();
        $email->setEmail($data['email']);
        $email->setConfirmed(false);
        $email->setPeople($people);
      }

      $this->manager->persist($email);
    }
  }

  public function createPhone(array $data, People $people): void
  {

    if (isset($data['phone']) && is_array($data['phone'])) {
      $phone = $this->manager->getRepository(Phone::class)
        ->findOneBy([
          'ddd'   => $data['phone']['ddd'],
          'phone' => $data['phone']['phone']
        ]);

      if ($phone instanceof Phone) {
        if ($phone->getPeople() instanceof People && $phone->getPeople() != $people)
          throw new \InvalidArgumentException('O telefone já esta em uso', 101);

        if ($phone->getPeople() === null)
          $phone->setPeople($people);
      } else {
        $phone = new Phone();
        $phone->setPeople($people);
        $phone->setDdd($data['phone']['ddd']);
        $phone->setPhone($data['phone']['phone']);
      }

      $this->manager->persist($phone);
    }
  }

  public function createUser(array $data): User
  {
    if (!isset($data['username']))
      throw new \InvalidArgumentException('Username not defined');

    if (empty($data['username']))
      throw new \InvalidArgumentException('Username value can not be empty');

    if (!isset($data['password']))
      throw new \InvalidArgumentException('Password not defined');

    if (empty($data['password']) || !is_string($data['password']))
      throw new \InvalidArgumentException('Password value can not be empty');

    if (strlen($data['password']) < 6)
      throw new \InvalidArgumentException('Password length cannot be less than 6 characters');

    $user = $this->manager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
    if ($user instanceof User)
      throw new \InvalidArgumentException('Este nome de usuário já existe. Escolha um nome diferente');

    $user = new User();

    $user->setUsername($data['username']);
    $user->setHash(
      $this->encoder->encodePassword($user, $data['password'])
    );

    return $user;
  }


  public function discovery(array $data, bool $flush = true)
  {
    $people = null;
    $document = null;
    /**
     * document
     *
     * @var Document
     */
    if (isset($data['document']))
    $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $data['document']]);
    if ($document) {
      return $document->getPeople();
    }

    /**
     * email
     *
     * @var Email
     */
    $email = isset($data['email']) ? $this->manager->getRepository(Email::class)->findOneBy(['email' => $data['email']]) : null;
    if ($email)
      $people = $email->getPeople();


    if (isset($data['phone']) && is_array($data['phone'])) {
      /**
       * phone
       *
       * @var Phone
       */
      $phone = $this->manager->getRepository(Phone::class)
        ->findOneBy([
          'ddd'   => $data['phone']['ddd'],
          'phone' => $data['phone']['phone']
        ]);
      if ($phone)
        $people = $phone->getPeople();
    }


    return $people;
  }

  public function create(array $data, bool $flush = true): People
  {
    $this->validateData($data);
    $people = $this->discovery($data, $flush);

    // create people

    if (!$people) {

      $people = new People();
      $people->setName($data['name']);
      $people->setAlias($data['alias']);
      $people->setPeopleType($data['type']);
      $people->setLanguage($this->getDefaultLanguage());
      $people->setEnabled(true);

      $this->manager->persist($people);
    }

    // create documents

    try {
      $this->createDocument($data, $people);
    } catch (\Exception $e) {
    }

    // create phone

    try {
      $this->createPhone($data, $people);
    } catch (\Exception $e) {
    }

    // create email

    try {
      $this->createEmail($data, $people);
    } catch (\Exception $e) {
    }

    if ($flush)
      $this->manager->flush();

    return $people;
  }

  public function documentExist(string $document, int $type)
  {
    $type = $this->manager->getRepository(DocumentType::class)->find($type);

    if ($type === null)
      return false;

    return $this->manager->getRepository(Document::class)
      ->findOneBy([
        'document'     => $document,
        'documentType' => $type,
      ]);
  }

  public function getDocumentTypeByDoc(string $document): ?string
  {
    return strlen($document) === 14 ? 'CNPJ' : (strlen($document) === 11 ? 'CPF' : null);
  }

  public function getPeopleDocumentTypeByDoc(string $document): ?DocumentType
  {
    $filter = [
      'documentType' => $this->getDocumentTypeByDoc($document),
      'peopleType'   => $this->getDocumentTypeByDoc($document) === 'CNPJ' ? 'J' : 'F',
    ];

    return $this->manager->getRepository(DocumentType::class)->findOneBy($filter);
  }

  public function getPeopleDocumentType(int $docId): ?DocumentType
  {
    return $this->manager->getRepository(DocumentType::class)->find($docId);
  }

  public function documentIsFree(string $document): bool
  {
    $doctype = $this->getPeopleDocumentTypeByDoc($document);
    // it is not valid
    if ($doctype === null) {
      return false;
    }
    // is it linked to someone?
    $document = $this->manager->getRepository(Document::class)
      ->findOneBy([
        'document'     => $document,
        'documentType' => $doctype,
      ]);
    if ($document instanceof Document) {
      return false;
    }

    return true;
  }

  public function createDocumentEntity(string $document): Document
  {
    if ($this->getDocumentTypeByDoc($document) === null) {
      throw new \InvalidArgumentException('O documento não é válido');
    }

    return (new Document())
      ->setDocument($document)
      ->setDocumentType($this->getPeopleDocumentTypeByDoc($document));
  }

  private function getDefaultLanguage(): ?Language
  {
    return $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
  }

  public function update(int $peopleId, array $data, bool $flush = true): People
  {

    $people = $this->manager->getRepository(People::class)->find($peopleId);

    if ($people !== null && $people instanceof People) {

      if (isset($data['name'])) {
        $people->setName($data['name']);
      }

      if (isset($data["alias"])) {
        $people->setAlias($data['alias']);
      }

      $this->manager->persist($people);

      // create documents

      try {
        $this->createDocument($data, $people);
      } catch (\Exception $e) {
      }

      // create phone

      try {
        $this->createPhone($data, $people);
      } catch (\Exception $e) {
      }

      // create email

      try {
        $this->createEmail($data, $people);
      } catch (\Exception $e) {
      }

      if ($flush)
        $this->manager->flush();
    }

    return $people;
  }

  private function validateData(array $data): void
  {
    if (!isset($data['type'])) {
      throw new \InvalidArgumentException('People type param is not defined');
    } else {
      if (!in_array($data['type'], ['J', 'F'], true)) {
        throw new \InvalidArgumentException('People type is not valid');
      }
    }



    if ($data['type'] === 'F') {


      if (!isset($data['email'])) {
        throw new \InvalidArgumentException('People email is not defined');
      } else {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
          throw new \InvalidArgumentException('People email is not valid');
        }
      }
    }

    if (!isset($data['name']) || empty($data['name'])) {
      throw new \InvalidArgumentException('People name is not valid');
    }
  }
}
