<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\GetSetting;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class GetSettingVoter extends Voter
{
    const READ = 'read';

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
        if (!in_array($attribute, [self::READ])) {
            return false;
        }

        if (!$subject instanceof GetSetting) {
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
         * @var GetSetting $getSetting
         */
        $getSetting = $subject;

        switch ($attribute) {
            case self::READ:
                return $this->canRead($getSetting, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canRead(GetSetting $getSetting, User $user)
    {
      return $this->isUserAdminInCompany($getSetting, $user);
    }

    private function isUserAdminInCompany(GetSetting $getSetting, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        if (!empty($getSetting->id)) {
          $company = $this->manager->getRepository(People::class)
            ->find($getSetting->id);

          if ($company instanceof People) {
            return $this->company->isMyCompany($company);
          }
        }
      }

      return false;
    }
}
