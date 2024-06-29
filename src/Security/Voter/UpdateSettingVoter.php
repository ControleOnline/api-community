<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\UpdateSetting;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class UpdateSettingVoter extends Voter
{
  const EDIT = 'edit';

  public function __construct(
    Security               $security,
    EntityManagerInterface $manager,
    PeopleRoleService      $roles,
    UserCompanyService     $company
  ) {
    $this->security = $security;
    $this->manager  = $manager;
    $this->roles    = $roles;
    $this->company  = $company;
  }

  protected function supports($attribute, $subject)
  {
    if (!in_array($attribute, [self::EDIT])) {
      return false;
    }

    if (!$subject instanceof UpdateSetting) {
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
     * @var UpdateSetting $updateSetting
     */
    $updateSetting = $subject;

    switch ($attribute) {
      case self::EDIT:
        return $this->canEdit($updateSetting, $user);
    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canEdit(UpdateSetting $updateSetting, User $user)
  {
    return $this->isUserAdminInCompany($updateSetting, $user);
  }

  private function isUserAdminInCompany(UpdateSetting $updateSetting, User $user): bool
  {
    $roles = $this->roles->getAllRoles($user);

    if (in_array('super', $roles) || in_array('franchisee', $roles)) {
      if (!empty($updateSetting->id)) {
        $company = $this->manager->getRepository(People::class)
          ->find($updateSetting->id);

        if ($company instanceof People) {
          return $this->company->isMyCompany($company);
        }
      }
    }

    return false;
  }
}
