<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\Person;

class AdminPersonPhonesAction
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

    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(Person $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => 'createPhone',
                Request::METHOD_DELETE => 'deletePhone',
                Request::METHOD_GET    => 'getPhones'  ,
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

    private function createPhone(Person $person, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            $phone   = $this->manager->getRepository(Phone::class)->findOneBy(['ddd' => $payload['ddd'], 'phone' => $payload['phone']]);

            if ($phone instanceof Phone) {
                if ($phone->getPeople() instanceof People) {
                    throw new \InvalidArgumentException('O telefone já esta em uso');
                }

                $phone->setPeople($company);
            }
            else {
                $phone = new Phone();
                $phone->setDdd      ($payload['ddd']);
                $phone->setPhone    ($payload['phone']);
                $phone->setConfirmed(false);
                $phone->setPeople   ($company);
            }

            $this->manager->persist($phone);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $phone->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deletePhone(Person $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Document id is not defined');
            }

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            
            $phone = $this->manager->getRepository(Phone::class)->findOneBy(['id' => $payload['id'], 'people' => $company]);
            if (!$phone instanceof Phone) {
                throw new \InvalidArgumentException('Person phone was not found');
            }

            $this->manager->remove($phone);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getPhones(Person $person, ?array $payload = null): array
    {
        $members = [];
        $company = $this->manager->getRepository(People::class )->find($person->getId());
        $phones  = $this->manager->getRepository(Phone::class)->findBy(['people' => $company]);

        foreach ($phones as $phone) {
            $members[] = [
                'id'    => $phone->getId(),
                'ddd'   => $phone->getDdd(),
                'phone' => $phone->getPhone(),
            ];
        }

        return [
            'members' => $members,
            'total'   => count($members),
        ];
    }
}
