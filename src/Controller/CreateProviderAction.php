<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use ControleOnline\Entity\Provider;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleProvider;
use Symfony\Component\HttpFoundation\Request;

class CreateProviderAction extends AbstractCustomResourceAction
{
  public function index(): ?array
  {
    if ($this->isPost()) {
      $provider = $this->createProvider();
    } elseif ($this->isPut()) {
      $provider = $this->updateProvider();
    }

    $document = $this->repository(Document::class)->findOneBy(['people' => $provider->getId()]);

    if ($document != null) {
      $document = $document->getDocument();
    }

    return [
      'id'         => $provider->getId(),
      'name'       => $provider->getName(),
      'alias'      => $provider->getAlias(),
      'peopleType' => $provider->getPeopleType(),
      'document'   => $document,
    ];
  }

  private function createProvider(): People
  {
    $payload = json_decode($this->request()->getContent());

    // validationif (isset($payload->document)) {

    if (isset($payload->document) && !empty($payload->document)) {
      if ($this->payload()->getPeopleType() == 'J') {
        if ($this->people()->getDocumentTypeByDoc($payload->document) !== 'CNPJ') {
          throw new \Exception(
            'Este documento não corresponde com uma pessoa jurídica.'
          );
        }
      }

      if ($this->payload()->getPeopleType() == 'F') {
        if ($this->people()->getDocumentTypeByDoc($payload->document) !== 'CPF') {
          throw new \Exception(
            'Este documento não corresponde com uma pessoa física.'
          );
        }
      }

      if (!$this->people()->documentIsFree($payload->document)) {
        throw new \Exception('Este documento não é válido ou já foi cadastrado');
      }
    }

    $this->manager()->getConnection()->beginTransaction();

    // create provider

    $provider = (new People)
      ->setName($this->payload()->getName())
      ->setAlias($this->payload()->getAlias())
      ->setPeopleType($this->payload()->getPeopleType())
      ->setEnabled(true)
      ->setLanguage(
        $this->repository(Language::class)->findOneBy(['language' => 'pt-BR'])
      );

    $this->manager()->persist($provider);

    // create provider document

    if (isset($payload->document) && !empty($payload->document)) {
      $document = $this->people()->createDocumentEntity($payload->document);
      $document->setPeople($provider);
      $this->manager()->persist($document);
    }

    // add people_provider

    $peopleProvider = (new PeopleProvider)
      ->setProvider($provider)
      ->setEnabled(true)
      ->setCompany(
        $this->company()->getMyCompany($this->request()->query->get('myCompany'))
      );

    $this->manager()->persist($peopleProvider);

    $this->manager()->flush();
    $this->manager()->getConnection()->commit();

    return $this->repository(People::class)->find($provider->getId());
  }

  private function updateProvider(): People
  {
    $payload  = json_decode($this->request()->getContent());
    $provider = $this->payload();
    $people   = $this->repository(People::class)->find($this->payload()->getId());

    $this->manager()->getConnection()->beginTransaction();

    /*
     * Provider entity link to document error in people entity.
     * Do not remove the next lines!
     */
    // >>>
    if ($provider->getDocument()) {
      $provider->getDocument()->setPeople($people);
    }
    // <<<

    // update peopleType
    if ($people->getPeopleType() != $payload->peopleType) {
      if (isset($payload->document) && !empty($payload->document)) {
        if ($this->payload()->getPeopleType() == 'J') {
          if ($this->people()->getDocumentTypeByDoc($payload->document) !== 'CNPJ') {
            throw new \Exception(
              'Este documento não corresponde com uma pessoa jurídica.'
            );
          }
        }

        if ($this->payload()->getPeopleType() == 'F') {
          if ($this->people()->getDocumentTypeByDoc($payload->document) !== 'CPF') {
            throw new \Exception(
              'Este documento não corresponde com uma pessoa física.'
            );
          }
        }
      }
    }

    // update provider document
    if (isset($payload->document) && !empty($payload->document)) {
      $oldDocument = $provider->getDocument() ? $provider->getDocument()->getDocument() : null;

      if ($payload->document !== $oldDocument) {
        if (!$this->people()->documentIsFree($payload->document)) {
          throw new \Exception('Este documento não é válido ou já foi cadastrado');
        }

        $document = $this->people()->createDocumentEntity($payload->document);

        $document->setPeople($people);

        $this->manager()->persist($document);

        if ($provider->getDocument() instanceof Document) {
          $this->manager()->remove($provider->getDocument());
        }
      }
    }

    $this->manager()->persist($provider);

    $this->manager()->flush();
    $this->manager()->getConnection()->commit();

    return $this->repository(People::class)->find($provider->getId());
  }
}
