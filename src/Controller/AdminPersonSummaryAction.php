<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;
use ControleOnline\Entity\PeopleClient;
use App\Service\PeopleService;

class AdminPeopleSummaryAction
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
                Request::METHOD_PUT => 'updateSummary',
                Request::METHOD_GET => 'getSummary',
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

    private function updateSummary(People $people, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($people->getId());

            if (isset($payload['name'])) {
                $people->setName($payload['name']);
            }

            if (isset($payload['alias'])) {
                $people->setAlias($payload['alias']);
            }


            if (isset($payload['type'])) {
                $people->setPeopleType($payload['type']);
            }

            if (isset($payload['birthday'])) {
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $payload['birthday']) === 1) {
                    $people->setFoundationDate(
                        \DateTime::createFromFormat('Y-m-d', $payload['birthday'])
                    );
                }
            }

            $this->manager->persist($people);

            if (isset($payload['particulars'])) {
                foreach ($payload['particulars'] as $particular) {
                    if ($particular['value'] === null)
                        continue;

                    // get particular type

                    if (($type = $this->manager->getRepository(ParticularsType::class)->find($particular['type'])) === null) {
                        throw new \InvalidArgumentException('People particular type was not found');
                    }

                    // particular type is the right one?

                    if ($type->getPeopleType() != $people->getPeopleType()) {
                        throw new \InvalidArgumentException('Particular type does not match with people type');
                    }

                    // is an update

                    if (isset($particular['id'])) {
                        $_particular = $this->manager->getRepository(Particulars::class)
                            ->findOneBy([
                                'id'     => $particular['id'],
                                'people' => $company,
                                'type'   => $type
                            ]);
                        if ($_particular === null) {
                            throw new \InvalidArgumentException('People particular data was not found');
                        }

                        $_particular->setValue($particular['value']);
                    } else {
                        // is an insert

                        $_particular = new Particulars();
                        $_particular->setType($type);
                        $_particular->setPeople($company);
                        $_particular->setValue($particular['value']);
                    }

                    $this->manager->persist($_particular);
                }
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return $this->getSummary($people);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getSummary(People $people, ?array $payload = null): array
    {
        $company = $this->manager->getRepository(People::class)->find($people->getId());

        return [
            'id'          => $people->getId(),
            'name'        => $people->getName(),
            'alias'       => $people->getAlias(),
            'type'        => $people->getPeopleType(),
            'enabled'      => $people->getEnabled(),
            'birthday'    => $people->getFoundationDate() !== null ? $people->getFoundationDate()->format('d/m/Y') : '',
            'particulars' => $this->getParticulars($company),
        ];
    }

    private function getParticulars(People $people): array
    {
        $particulars = [];

        $_particulars = $this->manager->getRepository(Particulars::class)->findBy(['people' => $people]);

        if (!empty($_particulars)) {
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
}
