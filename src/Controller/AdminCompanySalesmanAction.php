<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use App\Entity\PeopleClient;
use App\Entity\PeopleSalesman;
use App\Entity\People;
use App\Entity\Organization;
use ControleOnline\Entity\User;
use App\Entity\Document;
use App\Service\PeopleService;

class AdminCompanySalesmanAction
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

    public function __invoke(Organization $data, Request $request): JsonResponse
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
                    'success' => false,
                ],
            ]);

        }
    }

    private function createSalesman(Organization $company, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company  = $this->manager->getRepository(People::class)->find($company->getId());
            $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $payload['document'], 'documentType' => 3]);

            if ($document === null) {
                $salesman = $this->createPeopleSalesman($payload, $company);

                $contract = new PeopleSalesman();
                $contract->setCompany   ($company);
                $contract->setSalesman  ($salesman);
                $contract->setCommission(0);
                $contract->setEnabled   (1);

                $this->manager->persist($contract);
            }
            else {
                $salesman = $document->getPeople();

                $contract = $this->manager->getRepository(PeopleSalesman::class)
                  ->findOneBy([
                    'company'  => $company,
                    'salesman' => $salesman,
                  ]);

                if ($contract === null) {
                  $contract = new PeopleSalesman();
                  $contract->setCompany   ($company);
                  $contract->setSalesman  ($salesman);
                  $contract->setCommission(0);
                  $contract->setEnabled   (1);

                  $this->manager->persist($contract);
                }
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

    private function deleteSalesman(Organization $company, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Salesman id is not defined');
            }

            if ($company->getId() == $payload['id'])
                throw new \InvalidArgumentException('Can not delete your own people salesman');

            $salesman = $this->manager->getRepository(People::class)->find($payload['id']);
            if ($salesman === null)
                throw new \InvalidArgumentException('Salesman not found');

            $company  = $this->manager->getRepository(People::class)->find($company->getId());
            $contract = $this->manager->getRepository(PeopleSalesman::class)
                ->findOneBy([
                    'company'  => $company,
                    'salesman' => $salesman
                ]);

            if ($contract === null)
                throw new \InvalidArgumentException('Organization salesman relationship not found');

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

    private function getSalesman(Organization $company, ?array $payload = null): array
    {
        $salesman = $this->manager->getRepository(PeopleSalesman::class)
          ->getMySalesman(
            $this->manager->getRepository(People::class)->find($company->getId())
          );

        return [
            'members' => $salesman,
            'total'   => count($salesman),
        ];
    }

    private function createPeopleSalesman(array $payload, People $company): People
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

        return $salesman;
    }
}
