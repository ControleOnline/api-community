<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\CreateUser;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class CreateUserVoter extends Voter
{
    const CREATE = 'create';

    public function __construct(
      Security               $security,
      EntityManagerInterface $manager,
      PeopleRoleService      $roles,
      UserCompanyService     $company
    )
    {
        $this->security = $security;
        $this->manager  = $manager;
        $this->roles    = $roles;
        $this->company  = $company;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::CREATE])) {
            return false;
        }

        if (!$subject instanceof CreateUser) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /**
         * @var CreateUser $createUser
         */
        $createUser = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($createUser, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(CreateUser $createUser, User $user)
    {
      return $this->isUserAdminInCompany($createUser, $user);
    }

    private function isUserAdminInCompany(CreateUser $createUser, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        if (!empty($createUser->company)) {
          $company = $this->manager->getRepository(People::class)
            ->find($createUser->company);

          if ($company instanceof People) {
            return $this->company->isMyCompany($company);
          }
        }
      }

      return false;
    }
}
