<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Person;

class AdminPersonUsersAction
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

    /**
     * Password encoder
     *
     * @var UserPasswordEncoderInterface
     */
    private $encoder = null;

    public function __construct(EntityManagerInterface $manager, Security $security, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->encoder     = $passwordEncoder;
    }

    public function __invoke(Person $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => 'createUser',
                Request::METHOD_DELETE => 'deleteUser',
                Request::METHOD_GET    => 'getUsers'  ,
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

    private function createUser(Person $person, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['username']) || empty($payload['username'])) {
                throw new \InvalidArgumentException('Username param is not valid');
            }

            if (!isset($payload['password']) || empty($payload['password'])) {
                throw new \InvalidArgumentException('Password param is not valid');
            }

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            $user    = $this->manager->getRepository(User::class)->findOneBy(['username' => $payload['username']]);
            if ($user instanceof User) {
                throw new \InvalidArgumentException('O username já esta em uso');
            }

            $user = new User();
            $user->setUsername($payload['username']);
            $user->setHash    ($this->encoder->encodePassword($user, $payload['password']));
            $user->setPeople  ($company);

            $this->manager->persist($user);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $user->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteUser(Person $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Document id is not defined');
            }

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            $users   = $this->manager->getRepository(User::class)->findBy(['people' => $company]);
            if (count($users) == 1) {
                throw new \InvalidArgumentException('Deve existir pelo menos um usuário');
            }

            $user    = $this->manager->getRepository(User::class)->findOneBy(['id' => $payload['id'], 'people' => $company]);
            if (!$user instanceof User) {
                throw new \InvalidArgumentException('Person user was not found');
            }

            $this->manager->remove($user);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getUsers(Person $person, ?array $payload = null): array
    {
        $members = [];
        $company = $this->manager->getRepository(People::class )->find($person->getId());
        $users   = $this->manager->getRepository(User::class)->findBy(['people' => $company]);

        foreach ($users as $user) {
            $members[] = [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'apiKey'   => $user->getApiKey(),
            ];
        }

        return [
            'members' => $members,
            'total'   => count($members),
        ];
    }
}
