<?php

namespace App\Handler;

use ControleOnline\Entity\People;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use ControleOnline\Entity\RecoveryAccess;
use ControleOnline\Entity\User;

class RecoveryAccessHandler implements MessageHandlerInterface
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * Encoder
   *
   * @var UserPasswordEncoderInterface
   */
  private $encoder;

  public function __construct(EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder)
  {
    $this->manager = $manager;
    $this->encoder = $encoder;
  }

  public function __invoke(RecoveryAccess $recovery)
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $user = $this->recoveryAccess($recovery);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => $this->getUserLoggedData($user),
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
          'error'   => $e->getCode() >= 100 && $e->getCode() <= 103 ? $e->getMessage() : 'Não foi possivel recuperar sua senha. Tente novamente',
          'success' => false,
        ],
      ]);
    }
  }

  private function getUserLoggedData(User $user): array
  {
    // get contact data from user

    $email  = '';
    $code   = '';
    $number = '';

    if ($user->getPeople()->getEmail()->count() > 0)
      $email = $user->getPeople()->getEmail()->first()->getEmail();

    if ($user->getPeople()->getPhone()->count() > 0) {
      $phone  = $user->getPeople()->getPhone()->first();
      $code   = $phone->getDdd();
      $number = $phone->getPhone();
    }

    return [
      'username' => $user->getUsername(),
      'roles'    => $user->getRoles(),
      'api_key'  => $user->getApiKey(),
      'people'   => $user->getPeople()->getId(),
      'company'  => $this->getCompanyId($user),
      'realname' => $this->getUserRealName($user->getPeople()),
      'avatar'   => $user->getPeople()->getFile() ? '/files/download/' . $user->getPeople()->getFile()->getId() : null,
      'email'    => $email,
      'phone'    => sprintf('%s%s', $code, $number),
    ];
  }

  private function getUserRealName(People $people): string
  {
    $realName = 'John Doe';

    if ($people->getPeopleType() == 'J')
    $realName = $people->getAlias();

    else {
      if ($people->getPeopleType() == 'F') {
        $realName  = $people->getName();
        $realName .= ' ' . $people->getAlias();
        $realName  = trim($realName);
      }
    }

    return $realName;
  }

  private function getCompanyId(User $user): ?int
  {
    $peopleEmployee = $user->getPeople()->getPeopleCompany()->first();

    if ($peopleEmployee !== false && $peopleEmployee->getCompany() instanceof People)
      return $peopleEmployee->getCompany()->getId();

    return null;
  }

  private function recoveryAccess(RecoveryAccess $recovery): User
  {
    /**
     * @var \ControleOnline\Entity\User
     */
    $user = $this->manager->getRepository(User::class)->findOneBy(['lostPassword' => $recovery->lost]);

    if ($user === null)
      throw new \Exception('Access key not found', 100);

    /*
    if (!hash_equals($user->getLostPassword(), crypt($recovery->hash, $recovery->lost)))
      throw new \Exception('Incorrect access key', 101);
      */

    if ($recovery->hash !== $recovery->lost)
      throw new \Exception('Incorrect access key', 101);

    $user->setHash(
      $this->encoder->encodePassword($user, $recovery->password)
    );
    $user->setLostPassword(null);

    $this->manager->persist($user);

    return $user;
  }
}
