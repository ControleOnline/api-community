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
use ControleOnline\Entity\Email;
use App\Service\PeopleService;
use ControleOnline\Entity\PeopleClient;
use App\Service\PeopleRoleService;

class AdminPeopleEmployeesAction
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

            $methods = [
                Request::METHOD_PUT    => 'createEmployee',
                Request::METHOD_DELETE => 'deleteEmployee',
                Request::METHOD_GET    => 'getPeoples',
            ];

            $payload   = json_decode($this->request->getContent(), true);
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

    private function createEmployee(People $people, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['email'])) {
                throw new \InvalidArgumentException('Email param is not defined');
            }

            $company  = $this->manager->getRepository(People::class)->find($people->getId());
            $employee = null;
            $email    = $this->manager->getRepository(Email::class)->findOneBy(['email' => $payload['email']]);
            if ($email === null) {
                $params   = [
                    'name'  => $payload['name'],
                    'alias' => $payload['alias'],
                    'type'  => 'F',
                    'email' => $payload['email'],
                ];

                if (isset($payload['document'])) {
                    $params['documents'] = [];

                    $params['documents'][] = [
                        'document' => $payload['document'],
                        'type'     => 2
                    ];
                }

                if (isset($payload['phone']) && is_array($payload['phone'])) {
                    $params['phone'] = [
                        'ddd'   => $payload['phone']['ddd'],
                        'phone' => $payload['phone']['phone'],
                    ];
                }

                $employee = $this->people->create($params, false);

                // create employee user

                if (isset($payload['user']) && is_array($payload['user'])) {
                    $user = $this->people->createUser($payload['user']);

                    $user->setPeople($employee);

                    $this->manager->persist($user);
                }
            } else {
                $employee = $email->getPeople();
            }

            // create contract

            $contract = new PeopleLink();

            $contract->setCompany($company);
            $contract->setPeople($employee);
            $contract->setEnabled(true);

            $this->manager->persist($contract);

            // add as a customer

            $this->addEmployeeAsCustomer($employee);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $employee->getId()
            ];
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteEmployee(People $people, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Employee id is not defined');
            }

            $peopleLink = $this->manager->getRepository(PeopleLink::class)
                ->find($payload['id']);

            if ($peopleLink === null)
                throw new \InvalidArgumentException('People employee relationship not found');

            $this->manager->remove($peopleLink);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getPeoples(People $people, ?array $payload = null): array
    {
        $employees = [];

        foreach ($people->getCompany() as $peopleLink) {
            $email = $peopleLink->getPeople()->getEmail()->first();

            $employees[] = [
                'people_company_id' => $peopleLink->getId(),
                'id'    => $peopleLink->getPeople()->getId(),
                'name'  => $peopleLink->getPeople()->getName(),
                'alias' => $peopleLink->getPeople()->getAlias(),
                'email' => $email !== false ? $email->getEmail() : null,
            ];
        }

        return [
            'members' => $employees,
            'total'   => count($employees),
        ];
    }

    private function addEmployeeAsCustomer(People $employee)
    {
        $provider = $this->request->query->get('company', null);

        if (empty($provider) === false) {
            $provider = $this->manager->find(People::class, $provider);
            if ($provider === null) {
                throw new \InvalidArgumentException('Provider was not found');
            }

            // create provider employee link



            $providerEmployee    = $this->manager->getRepository(PeopleClient::class)->findOneBy(
                [
                    'client' => $employee,
                    'company_id' => $provider->getId()
                ]
            );

            if (!$providerEmployee) {
                $providerEmployee = new PeopleClient();
                $providerEmployee->setCompanyId($provider->getId());
                $providerEmployee->setClient($employee);
                $providerEmployee->setEnabled(true);

                $this->manager->persist($providerEmployee);
            }

            // create salesman employee link            

            if ($this->peopleRoles->isSalesman($this->currentUser->getPeople())) {
                $companies = $this->currentUser->getPeople() ?
                    $this->currentUser->getPeople()->getLink() : null;

                if (empty($companies) || $companies->first() === false) {
                    throw new \Exception('Salesman without companies');
                }

                $salesmanEmployee    = $this->manager->getRepository(PeopleClient::class)->findOneBy(
                    [
                        'client' => $employee,
                        'company_id' => $companies->first()->getCompany()->getId()
                    ]
                );

                if ($salesmanEmployee) {
                    $salesmanEmployee = new PeopleClient();
                    $salesmanEmployee->setCompanyId($companies->first()->getCompany()->getId());
                    $salesmanEmployee->setClient($employee);
                    $salesmanEmployee->setEnabled(true);
                    $this->manager->persist($salesmanEmployee);
                }
            }
        }
    }
}
