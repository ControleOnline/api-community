<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\People;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Document;
use App\Service\PeopleService;
use ControleOnline\Entity\PeopleClient;
use App\Service\PeopleRoleService;

class AdminPeopleCompaniesAction
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
  private $request  = null;

  /**
   * People Service
   *
   * @var \App\Service\PeopleService
   */
  private $people   = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * Current user
   *
   * @var \ControleOnline\Entity\User
   */
  private $currentUser = null;

  public function __construct(EntityManagerInterface $manager, PeopleService $people, Security $security, PeopleRoleService $roles)
  {
    $this->manager     = $manager;
    $this->people      = $people;
    $this->security    = $security;
    $this->currentUser = $security->getUser();
    $this->peopleRoles = $roles;
  }

  public function __invoke(People $data, Request $request): JsonResponse
  {
    $this->request = $request;

    try {

      $payload = json_decode($this->request->getContent(), true);
      $methods = [
        Request::METHOD_PUT    => 'createCompany',
        Request::METHOD_DELETE => 'deleteCompany',
        Request::METHOD_GET    => 'getCompanies',
      ];

      if ($this->request->getMethod() == Request::METHOD_PUT) {
        if (isset($payload['update']) && is_array($payload['update'])) {
          $methods[Request::METHOD_PUT] = 'updateCompany';
        }
      }

      $operation = $methods[$request->getMethod()];
      $result    = $this->$operation($data, $payload);

      return new JsonResponse([
        'response' => [
          'data'    => $result,
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ], 200);
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

  private function createCompany(People $people, array $payload): ?array
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      if (!isset($payload['document'])) {
        throw new \InvalidArgumentException('Company document is not defined');
      }

      if (!isset($payload['name'])) {
        throw new \InvalidArgumentException('Company name is not defined');
      }

      $docType = $this->people->getDocumentTypeByDoc($payload['document']);
      if ($docType !== 'CNPJ') {
        throw new \InvalidArgumentException('Company document is not valid');
      }

      $employee = $this->manager->getRepository(People::class)->find($people->getId());
      $company  = null;
      $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $payload['document']]);
      if ($document === null) {
        $params  = [
          'name'      => $payload['name'],
          'alias'     => $payload['alias'],
          'type'      => 'J',
          'documents' => [
            [
              'type'     => 3,
              'document' => $payload['document'],
            ]
          ]
        ];
        $company = $this->people->create($params, false);

        $this->manager->persist($company);
      } else {
        $company = $document->getPeople();
      }

      // create contract

      $contract = new PeopleLink();

      $contract->setCompany($company);
      $contract->setPeople($employee);
      $contract->setEnabled(true);

      $this->manager->persist($contract);

      // add as a customer

      $this->addCompanyAsCustomer($company);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return [
        'id' => $company->getId()
      ];
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      throw new \InvalidArgumentException($e->getMessage());
    }
  }

  private function updateCompany(People $people, array $payload): ?array
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $payload = $payload['update'];

      if (!isset($payload['id'])) {
        throw new \InvalidArgumentException('Company id is not defined');
      }

      $employee = $this->manager->getRepository(People::class)->find($people->getId());

      $company  = $this->manager->getRepository(People::class)->find($payload['id']);
      $company  = $this->manager->getRepository(PeopleLink::class)
        ->findOneBy([
          'company'  => $company,
          'employee' => $employee
        ]);
      if ($company === null) {
        throw new \InvalidArgumentException('Company not found');
      }
      $company  = $company->getCompany();

      if (isset($payload['document'])) {
        $docType = $this->people->getDocumentTypeByDoc($payload['document']);
        if ($docType !== 'CNPJ') {
          throw new \InvalidArgumentException('Company document is not valid');
        }

        $document = $this->manager->getRepository(Document::class)
          ->findOneBy(['document' => $payload['document']]);

        if ($document instanceof Document) {
          if ($document->getPeople() != $company) {
            throw new \InvalidArgumentException('Este CNPJ pertence a outra empresa');
          }
        } else {
          $document = $this->manager->getRepository(Document::class)
            ->findOneBy([
              'people'       => $company,
              'documentType' => $this->people->getPeopleDocumentType(3)
            ]);

          if ($document instanceof Document) {
            $document->setDocument($payload['document']);
            $this->manager->persist($document);
          }
        }
      }

      if (isset($payload['name'])) {
        $company->setName($payload['name']);
      }

      if (isset($payload['alias'])) {
        $company->setAlias($payload['alias']);
      }

      $this->manager->persist($company);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return [
        'id' => $company->getId()
      ];
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      throw new \InvalidArgumentException($e->getMessage());
    }
  }

  private function deleteCompany(People $people, array $payload): bool
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      if (!isset($payload['id'])) {
        throw new \InvalidArgumentException('Company id is not defined');
      }

      if ($people->getId() == $payload['id'])
        throw new \InvalidArgumentException('Can not delete your own people company');

      $company = $this->manager->getRepository(People::class)->find($payload['id']);
      if ($company === null)
        throw new \InvalidArgumentException('Company not found');

      $contract = $this->manager->getRepository(PeopleLink::class)
        ->findOneBy([
          'company'  => $company,
          'employee' => $people
        ]);

      if ($contract === null)
        throw new \InvalidArgumentException('People company relationship not found');

      // remove company people and PeopleLink

      $this->manager->remove($company);
      $this->manager->remove($contract);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return true;
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      throw new \InvalidArgumentException($e->getMessage());
    }
  }

  private function getCompanies(People $people, ?array $payload = null): array
  {
    $companies = [];

    foreach ($people->getPeopleCompany() as $peopleCompany) {
      $email    = $peopleCompany->getCompany()->getEmail()->first();
      $document = false;

      foreach ($peopleCompany->getCompany()->getDocument() as $document) {
        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
          $document = $document->getDocument();
        }
      }

      $companies[] = [
        'people_company_id'       => $peopleCompany->getId(),
        'id'       => $peopleCompany->getCompany()->getId(),
        'name'     => $peopleCompany->getCompany()->getName(),
        'alias'    => $peopleCompany->getCompany()->getAlias(),
        'email'    => $email !== false ? $email->getEmail() : null,
        'document' => $document !== false ? $document : null,
      ];
    }

    return [
      'members' => $companies,
      'total'   => count($companies),
    ];
  }

  private function addCompanyAsCustomer(People $company)
  {
    $provider = $this->request->query->get('company', null);

    if (empty($provider) === false) {
      $provider = $this->manager->find(People::class, $provider);
      if ($provider === null) {
        throw new \InvalidArgumentException('Provider was not found');
      }
      
      $providerCompany =
        $this->manager->getRepository(PeopleClient::class)->findOneBy([
          'company_id' => $provider->getId(),
          'client' => $company
        ]);
      if (!$providerCompany) {

        // create provider company link
        $providerCompany = new PeopleClient();
        $providerCompany->setCompanyId($provider->getId());
        $providerCompany->setClient($company);
        $providerCompany->setEnabled(true);
        $this->manager->persist($providerCompany);
      }



      // create salesman company link      

      if ($this->peopleRoles->isSalesman($this->currentUser->getPeople())) {
        $companies = $this->currentUser->getPeople() ?
          $this->currentUser->getPeople()->getPeopleCompany() : null;

        if (empty($companies) || $companies->first() === false) {
          throw new \Exception('Salesman without companies');
        }

        $salesmanCompany = new PeopleClient();
        $salesmanCompany->setCompanyId($companies->first()->getCompany()->getId());
        $salesmanCompany->setClient($company);
        $salesmanCompany->setEnabled(true);

        $this->manager->persist($salesmanCompany);
      }
    }
  }
}
