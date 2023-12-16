<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use App\Service\PeopleService;
use App\Service\PeopleRoleService;
use App\Service\AddressService;

class CreatePeopleCustomerAction
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
     * Address Service
     *
     * @var \App\Service\AddressService
     */
    private $address  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

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

    public function __construct(
        EntityManagerInterface $manager,
        PeopleService          $people,
        Security               $security,
        PeopleRoleService      $roles,
        AddressService         $address
    ) {
        $this->manager     = $manager;
        $this->people      = $people;
        $this->address     = $address;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->clients     = $this->manager->getRepository(PeopleClient::class);
        $this->peopleRoles = $roles;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $customer = json_decode($this->request->getContent(), true);

            $this->validateData($customer);

            $this->manager->getConnection()->beginTransaction();

            $people = $this->createCustomer($customer);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'peopleId' => $people->getId(),
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

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

    /**
     * @param array $data
     * [
     *   'name'     => '',
     *   'alias'    => '',
     *   'type'     => '',
     *   'email'    => '',
     *   'document' => '',
     *   'address'  => [
     *     'country'    => '',
     *     'state'      => '',
     *     'city'       => '',
     *     'district'   => '',
     *     'complement' => '',
     *     'street'     => '',
     *     'number'     => '',
     *     'postalCode' => '',
     *   ],
     * ]
     * @return array
     */
    public function createCustomer(array $data): People
    {
        /*
      if ($this->clients->clientBelongsToOtherSalesman($data['document'], $this->getMySalesCompany()))
          throw new \Exception('Este cliente já pertence a outro vendedor');
      */

        if (($customer = $this->getPeopleCustomer($data)) === null) {
            if ($data['type'] === 'F') {
                $customer = $this->createPFClient($data);
            }

            if ($data['type'] === 'J') {
                $customer =  $this->createPJClient($data);
            }
        }



        return $this->peopleRoles->isSalesman($this->currentUser->getPeople()) ? $this->setPeopleAsMyCustomer($customer) : $customer;
    }

    private function createPFClient(array $data): People
    {
        $params = [
            'name'      => $data['name'],
            'alias'     => $data['alias'],
            'type'      => 'F',
            'email'     => $data['email'],
            'documents' => []
        ];

        if (isset($data['document'])) {
            $params['documents'][] = ['document' => $data['document'], 'type' => 2];
        }

        $people = $this->people->create($params, false);

        $this->manager->persist($people);

        if (isset($data['address'])) {
            $address = $this->address->createFor($people, $data['address']);

            if ($address === null) {
                throw new \Exception('O endereço não é válido');
            }

            $this->manager->persist($address);
        }

        return $people;
    }

    private function createPJClient(array $data): People
    {
        $params = [
            'name'      => $data['name'],
            'alias'     => $data['alias'],
            'type'      => 'J',
            'documents' => [
                ['document' => $data['document'], 'type' => 3],
            ],
        ];
        $people = $this->people->create($params, false);

        $this->manager->persist($people);

        if (isset($data['address'])) {
            $address = $this->address->createFor($people, $data['address']);

            if ($address === null) {
                throw new \Exception('O endereço não é válido');
            }

            $this->manager->persist($address);
        }

        return $people;
    }

    private function getPeopleCustomer(array $data): ?People
    {
        if ($data['type'] === 'J') {
            $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $data['document']]);

            return $document instanceof Document ? $document->getPeople() : null;
        }

        if ($data['type'] === 'F') {
            $email = $this->manager->getRepository(Email::class)->findOneBy(['email' => $data['email']]);

            return $email instanceof Email ? $email->getPeople() : null;
        }
    }

    private function validateData(array $data): void
    {
        if (!isset($data['type']))
            throw new \InvalidArgumentException('Customer type param is not defined');
        else {
            if (!in_array($data['type'], ['J', 'F'], true))
                throw new \InvalidArgumentException('Customer type is not valid');
        }

        if ($data['type'] === 'J') {
            if (!isset($data['document']))
                throw new \InvalidArgumentException('Customer document is not defined');
            else {
                $docType = $this->people->getDocumentTypeByDoc($data['document']);
                if ($docType !== 'CNPJ')
                    throw new \InvalidArgumentException('Customer document is not valid');
            }
        }

        if ($data['type'] === 'F') {
            if (!isset($data['email']))
                throw new \InvalidArgumentException('Customer email is not defined');
            else {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
                    throw new \InvalidArgumentException('Customer email is not valid');
            }

            if (isset($data['document'])) {
                if ($this->people->getDocumentTypeByDoc($data['document']) !== 'CPF') {
                    throw new \InvalidArgumentException('Customer document is not valid');
                }
            }
        }

        if (!isset($data['name']) || empty($data['name']))
            throw new \InvalidArgumentException('Customer name is not valid');
    }

    private function setPeopleAsMyCustomer(People $people): People
    {
        // get salesman company

        $company  = $this->getMySalesmanCompany();

        // get my provider

        $provider = $this->getMyProvider();

        if ($company->getId() == $provider->getId()) {
            if (!$this->customerRelationshipExists($company, $people)) {
                $salesmanClient = new PeopleClient();
                $salesmanClient->setCompanyId($company->getId());
                $salesmanClient->setClient($people);
                $salesmanClient->setEnabled(false);

                $this->manager->persist($salesmanClient);

                return $people;
            }
        }

        // create salesman people relationship

        if (!$this->customerRelationshipExists($company, $people)) {
            $salesmanClient = new PeopleClient();
            $salesmanClient->setCompanyId($company->getId());
            $salesmanClient->setClient($people);
            $salesmanClient->setEnabled(false);

            $this->manager->persist($salesmanClient);
        }

        // create provider people relationship

        if (!$this->customerRelationshipExists($provider, $people)) {
            $providerClient = new PeopleClient();
            $providerClient->setCompanyId($provider->getId());
            $providerClient->setClient($people);
            $providerClient->setEnabled(true);

            $this->manager->persist($providerClient);
        }

        return $people;
    }

    private function getMySalesmanCompany(): People
    {
        $repository = $this->manager->getRepository(People::class);

        $companies  = $repository->createQueryBuilder('P')
            ->select()
            ->innerJoin('\ControleOnline\Entity\PeopleEmployee', 'PE', 'WITH', 'PE.company = P.id')
            ->innerJoin('\ControleOnline\Entity\PeopleSalesman', 'PS', 'WITH', 'PS.salesman = PE.company')
            ->where('PE.employee = :employee')
            ->setParameters([
                'employee' => $this->currentUser->getPeople()
            ])
            ->groupBy('P.id')
            ->getQuery()
            ->getResult();

        if (empty($companies))
            throw new \Exception('Sua empresa não está cadastrada no sistema');

        return $companies[0];
    }

    private function getMyProvider(): People
    {
        $providerId = $this->request->query->get('myProvider', null);
        if ($providerId === null) {
            throw new \InvalidArgumentException('Provider Id is not defined');
        }

        $peopleRepo = $this->manager->getRepository(People::class);
        $provider   = $peopleRepo->find($providerId);

        if ($provider === null) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $salesman   = $this->manager->getRepository(PeopleSalesman::class);
        $myUser     = $this->security->getUser();

        if (!$salesman->companyIsMyProvider($myUser->getPeople(), $provider)) {
            throw new \InvalidArgumentException('Your Company does not work with this Provider');
        }

        return $provider;
    }

    private function customerRelationshipExists(People $company, People $customer): bool
    {
        return ($this->manager->getRepository(PeopleClient::class)
            ->findOneBy(['company_id' => $company->getId(), 'client' => $customer])) === null ? false : true;
    }
}
