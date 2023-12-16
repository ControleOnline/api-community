<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Person;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;
use ControleOnline\Entity\PeopleClient;
use App\Service\PeopleService;

class AdminPersonSummaryAction
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

    public function __invoke(Person $data, Request $request): JsonResponse
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

    private function updateSummary(Person $person, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($person->getId());

            if (isset($payload['name'])) {
                $person->setName($payload['name']);
            }

            if (isset($payload['alias'])) {
                $person->setAlias($payload['alias']);
            }


            if (isset($payload['type'])) {
                $person->setPeopleType($payload['type']);
            }

            if (isset($payload['birthday'])) {
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $payload['birthday']) === 1) {
                    $person->setFoundationDate(
                        \DateTime::createFromFormat('Y-m-d', $payload['birthday'])
                    );
                }
            }

            $this->manager->persist($person);

            if (isset($payload['particulars'])) {
                foreach ($payload['particulars'] as $particular) {
                    if ($particular['value'] === null)
                        continue;

                    // get particular type

                    if (($type = $this->manager->getRepository(ParticularsType::class)->find($particular['type'])) === null) {
                        throw new \InvalidArgumentException('Person particular type was not found');
                    }

                    // particular type is the right one?

                    if ($type->getPeopleType() != $person->getPeopleType()) {
                        throw new \InvalidArgumentException('Particular type does not match with person type');
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
                            throw new \InvalidArgumentException('Person particular data was not found');
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

            return $this->getSummary($person);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getSummary(Person $person, ?array $payload = null): array
    {
        $company = $this->manager->getRepository(People::class)->find($person->getId());

        return [
            'id'          => $person->getId(),
            'name'        => $person->getName(),
            'alias'       => $person->getAlias(),
            'type'        => $person->getPeopleType(),
            'enabled'      => $person->getEnabled(),
            'birthday'    => $person->getFoundationDate() !== null ? $person->getFoundationDate()->format('d/m/Y') : '',
            'particulars' => $this->getParticulars($company),
        ];
    }

    private function getParticulars(People $person): array
    {
        $particulars = [];

        $_particulars = $this->manager->getRepository(Particulars::class)->findBy(['people' => $person]);

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
