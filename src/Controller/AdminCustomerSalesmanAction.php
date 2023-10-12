<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use App\Entity\PeopleClient;
use App\Entity\PeopleSalesman;
use App\Entity\People;

use ControleOnline\Entity\User;
use App\Entity\Document;
use App\Service\PeopleService;

class AdminCustomerSalesmanAction
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

    public function __construct(EntityManagerInterface $manager, PeopleService $people, Security $security)
    {
        $this->manager     = $manager;
        $this->people      = $people;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => 'createSalesman',
                Request::METHOD_DELETE => 'deleteSalesman',
                Request::METHOD_GET    => 'getSalesman',
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
                    'file'   => $e->getFile(),
                    'line'   => $e->getLine(),
                    'success' => false,
                ],
            ]);
        }
    }

    private function createSalesman(People $customer, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company  = $this->manager->getRepository(People::class)->find($customer->getId());
            $provider = $this->getMyProvider();
            $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $payload['document'], 'documentType' => 3]);

            // get salesman

            if ($document === null) {
                $salesman = $this->createPeopleSalesman($payload, $provider);
            } else {
                $salesman = $document->getPeople();
                if ($salesman === null) {
                    $salesman = $this->createPeopleSalesman($payload, $provider);
                }
            }

            // allowed only one salesman by customer

            if ($this->manager->getRepository(PeopleSalesman::class)->customerHasSalesman($company)) {
                throw new \InvalidArgumentException('Este cliente já tem um vendedor');
            }

            $people_salesman = $this->manager->getRepository(PeopleSalesman::class)
                ->findOneBy([
                    'company'  => $provider,
                    'salesman' => $salesman
                ]);

            if (!$this->customerRelationshipExists($salesman, $company)) {
                // create contract
                $contract = new PeopleClient();
                $contract->setCompanyId($salesman->getId());
                $contract->setClient($company);
                $contract->setEnabled(true);
                $contract->setCommission($people_salesman->getCommission());

                $this->manager->persist($contract);
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $salesman->getId()
            ];
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteSalesman(People $customer, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Salesman id is not defined');
            }

            if ($customer->getId() == $payload['id'])
                throw new \InvalidArgumentException('Can not delete your own people salesman');

            $salesman = $this->manager->getRepository(People::class)->find($payload['id']);
            if ($salesman === null)
                throw new \InvalidArgumentException('Salesman not found');

            $company  = $this->manager->getRepository(People::class)->find($customer->getId());
            $contract = $this->manager->getRepository(PeopleClient::class)
                ->findOneBy([
                    'company_id' => $salesman->getId(),
                    'client'     => $company
                ]);

            if ($contract === null)
                throw new \InvalidArgumentException('Customer salesman relationship not found');

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

    private function getSalesman(People $customer, ?array $payload = null): array
    {
        $company  = $this->manager->getRepository(People::class)->find($customer->getId());
        $salesman = $this->manager->getRepository(PeopleClient::class)->getCustomerSalesman($company);

        return [
            'members' => $salesman,
            'total'   => count($salesman),
        ];
    }

    private function createPeopleSalesman(array $payload, People $provider): People
    {
        $params   = [
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
        $salesman = $this->people->create($params, false);

        $this->manager->persist($salesman);

        $contract = new PeopleSalesman();
        $contract->setCompany($provider);
        $contract->setSalesman($salesman);
        $contract->setCommission(2.8);
        $contract->setEnabled(1);

        $this->manager->persist($contract);

        return $salesman;
    }

    private function customerRelationshipExists(People $company, People $customer): bool
    {
        return ($this->manager->getRepository(PeopleClient::class)
            ->findOneBy(['company_id' => $company->getId(), 'client' => $customer])) === null ? false : true;
    }

    private function getMyProvider(): People
    {
        $id = $this->request->query->get('myProvider', null);
        if (empty($id)) {
            throw new \InvalidArgumentException('Provider id was not defined');
        }

        $provider = $this->manager->getRepository(People::class)->find($id);
        if ($provider === null) {
            throw new \InvalidArgumentException('Provider not found');
        }

        $salesman = $this->manager->getRepository(PeopleSalesman::class);

        if (!$salesman->companyIsMyProvider($this->security->getUser()->getPeople(), $provider)) {
            throw new \InvalidArgumentException('Your Company does not work with this Provider');
        }

        return $provider;
    }
}
