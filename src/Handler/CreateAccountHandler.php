<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use ControleOnline\Entity\Account;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\Phone;

class CreateAccountHandler implements MessageHandlerInterface
{
    private $manager;

    private $encoder;

    public function __construct(EntityManagerInterface $manager, UserPasswordEncoderInterface $passwordEncoder)
    {
      $this->manager = $manager;
      $this->encoder = $passwordEncoder;
    }

    public function __invoke(Account $account)
    {
      try {
        $this->manager->getConnection()->beginTransaction();

        $user = $this->createAccount($account);

        $this->manager->flush();
        $this->manager->getConnection()->commit();

        return new JsonResponse([
          'response' => [
            'data'    => [
              'id'       => $user->getId(),
              'username' => $user->getUsername(),
              'appkey'   => $user->getApiKey(),
              'people'   => [
                'id'      => $user->getPeople()->getId(),
                'company' => $this->getCompanyId($user),
              ],
            ],
            'count'   => 1,
            'success' => true,
          ],
        ]);
      } catch (\Exception $e) {
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

    private function createAccount(Account $account): User
    {
      $email = $this->manager->getRepository(Email::class)->findOneBy(['email' => $account->email]);

      if ($email !== null) {
        if (($people = $email->getPeople()) !== null) {
          if ($people->getUser()->count() > 0)
            throw new \Exception('This account already exists');
          else {
            if ($this->accountAlreadyExist($account->username))
              throw new \Exception('This user already exists.');

            $user = $this->createUser($account->username, $account->password);

            $people->setEnabled(true);
            $this->manager->persist($people);

            $user->setPeople($people);
            $this->manager->persist($user);

            return $user;
          }
        }
      }

      if ($this->accountAlreadyExist($account->username))
        throw new \Exception('This user already exists.');

      $people = new People();
      $people->setName      ($account->name);
      $people->setPeopleType('F');
      $people->setLanguage  ($this->getDefaultLanguage());
      $people->setAlias     ('');
      $people->setEnabled   (true);
      $this->manager->persist($people);

      $phone = new Phone();
      $phone->setPeople($people);
      $phone->setDdd   ($account->ddd);
      $phone->setPhone ($account->phone);
      $this->manager->persist($phone);

      $user = $this->createUser($account->username, $account->password);
      $user->setPeople($people);
      $this->manager->persist($user);

      $email = new Email();
      $email->setPeople($people);
      $email->setEmail ($account->email);
      $this->manager->persist($email);

      return $user;
    }

    private function createUser(string $username, string $password): User
    {
      $user = new User();

      $user->setUsername($username);
      $user->setHash    (
        $this->encoder->encodePassword($user, $password)
      );

      return $user;
    }

    private function accountAlreadyExist(string $username): bool
    {
      $userRepo = $this->manager->getRepository(User::class);

      if ($userRepo->findOneBy(['username' => $username]) !== null)
        return true;

      return false;
    }

    private function getDefaultLanguage(): ?Language
    {
      return $this->manager->getRepository(Language::class)
        ->findOneBy(['language' => 'pt-BR']);
    }

    private function getCompanyId(User $user)
    {
      $peopleEmployee = $user->getPeople()->getPeopleCompany()->first();

      if ($peopleEmployee !== false && $peopleEmployee->getCompany() instanceof People)
        return $peopleEmployee->getCompany()->getId();

      return null;
    }
}
