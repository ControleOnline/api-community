<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Person;
use ControleOnline\Entity\Address;
use App\Service\AddressService;

class AdminPersonAddressesAction
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
     * Address Service
     *
     * @var \App\Service\AddressService
     */
    private $address   = null;

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

    public function __construct(EntityManagerInterface $manager, AddressService $address, Security $security)
    {
        $this->manager     = $manager;
        $this->address     = $address;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(Person $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => 'createAddress',
                Request::METHOD_DELETE => 'deleteAddress',
                Request::METHOD_GET    => 'getAddress'   ,
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

    private function createAddress(Person $person, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            $address = $this->address->createFor($company, $payload);

            if ($address === null)
                throw new \InvalidArgumentException('Ocorreu um erro ao tentar cadastrar o endereço');

            $this->manager->persist($address);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $address->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteAddress(Person $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Address id is not defined');
            }

            $company = $this->manager->getRepository(People::class)->find($person->getId());                        

            $address = $this->manager->getRepository(Address::class)->findOneBy(['id' => $payload['id'], 'people' => $company]);

            if (!$address instanceof Address) {
                throw new \InvalidArgumentException('Person address was not found');
            }

            $address->setPeople(null);

            $this->manager->persist($address);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getAddress(Person $person, ?array $payload = null): array
    {
        $members   = [];
        $company   = $this->manager->getRepository(People::class )->find($person->getId());
        $addresses = $this->manager->getRepository(Address::class)->findBy(['people' => $company]);

        foreach ($addresses as $address) {
            $members[] = $this->address->addressToArray($address);
        }

        return [
            'members' => $members,
            'total'   => count($members),
        ];
    }
}
