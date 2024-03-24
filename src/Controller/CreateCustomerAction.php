<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\PeopleProvider;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PeopleLink;
use App\Service\PeopleService;
use App\Service\PeopleRoleService;

class CreateCustomerAction
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

    public function __construct(EntityManagerInterface $manager, PeopleService $people, Security $security, PeopleRoleService $roles)
    {
        $this->manager     = $manager;
        $this->people      = $people;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->clients     = $this->manager->getRepository(PeopleClient::class);
        $this->peopleRoles = $roles;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $payload = json_decode($this->request->getContent(), true);

            $table = $this->request->query->get("table", null);
            $myProvider = $this->request->query->get("myProvider", null);
            $employeeId = $this->request->query->get("employeeId", null);

            if ($table !== null) {
                unset($payload["table"]);
            }

            $customer = $payload;

            $this->validateData($customer);

            $this->manager->getConnection()->beginTransaction();

            $people = $this->createCustomer($customer);


            if ($employeeId) {
                $prov = new PeopleLink();
                $prov->setPeople($this->manager->getRepository(People::class)->find($employeeId));
                $prov->setCompany($people);
                $prov->setEnabled(1);
                $this->manager->persist($prov);
            }

            if ($table === "provider") {

                $company = $this->manager->getRepository(People::class)
                    ->findOneBy(array(
                        "id" => $myProvider
                    ));


                $peopleProvider = $this->manager->getRepository(PeopleProvider::class)
                    ->findOneBy(array(
                        "provider" => $people,
                        'company' => $company
                    ));

                if (!$peopleProvider) {
                    $prov = new PeopleProvider();
                    $prov->setProvider($people);
                    $prov->setCompany($company);
                    $prov->setEnabled(1);

                    $this->manager->persist($prov);
                }
            }

            if ($table === "client") {
                $company = $this->manager->getRepository(People::class)
                    ->findOneBy(array(
                        "id" => $myProvider
                    ));

                $peopleClient = $this->manager->getRepository(PeopleClient::class)
                    ->findOneBy(array(
                        "client" => $people,
                        'company_id' => $company->getId()
                    ));

                if (!$peopleClient) {
                    $prov = new PeopleClient();
                    $prov->setClient($people);
                    $prov->setCompanyId($company->getId());
                    $prov->setEnabled(1);
                    $this->manager->persist($prov);
                }
            }


            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'customerId' => $people->getId(),
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 201);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage() . $e->getLine(),
                    'success' => false,
                ],
            ]);
        }
    }

    /**
     * @param array $data
     * {
     *   "type"    : "J",
     *   "name"    : "Cliente",
     *   "alias"   : "Modelo",
     *   "document": "85588888888",
     *   "email"   : "email@gmail.com"
     * }
     * @return People
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

            // fix customer finance configs

            $cEntity = new People();
            $customer->setBillingDays($cEntity->getBillingDays());
            $customer->setPaymentTerm($cEntity->getPaymentTerm());
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


        /**
         * Procura o e-mail
         */
        $email = $this->manager->getRepository(Email::class)
            ->findOneBy(array(
                "email" => $data['email']
            ));

        if ($email) {
            /**
             * Se existe, retorna a pessoa
             */
            $people = $email->getPeople();
        } else {
            /**
             * Se não existe, cadastra e retorna a pessoa
             */
            $people = $this->people->create($params, false);
        }

        $this->manager->persist($people);

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

        $document = $this->manager->getRepository(Document::class)
            ->findOneBy(array(
                "document" => $data['document']
            ));

        if ($document) {
            $people = $document->getPeople();
        } else {
            $people = $this->people->create($params, false);
        }

        $this->manager->persist($people);

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
            ->innerJoin('\ControleOnline\Entity\PeopleLink', 'PE', 'WITH', 'PE.company = P.id')
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

    /*
    private function getMySalesCompany(): People
    {
        return ($this->currentUser->getPeople()->getPeopleCompany()->first())
            ->getCompany();
    }
    */
}
