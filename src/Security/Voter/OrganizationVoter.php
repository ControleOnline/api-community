<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use ControleOnline\Entity\Organization;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class OrganizationVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

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
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Organization) {
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
         * @var Organization $organization
         */
        $organization = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($organization, $user);
            case self::READ  :
                return $this->canRead  ($organization, $user);
            case self::EDIT  :
                return $this->canEdit  ($organization, $user);
            case self::DELETE:
                return $this->canDelete($organization, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(Organization $organization, User $user)
    {
      return $this->userBelongsToCompany($organization, $user);
    }

    private function canRead(Organization $organization, User $user)
    {
      return $this->userBelongsToCompany($organization, $user);
    }

    private function canEdit(Organization $organization, User $user)
    {
      return $this->userBelongsToCompany($organization, $user);
    }

    private function canDelete(Organization $organization, User $user)
    {
      return $this->userBelongsToCompany($organization, $user);
    }

    private function userBelongsToCompany(Organization $organization, User $user): bool
    {
      return $this->company->isMyCompany(
        $this->manager->getRepository(People::class)->find($organization->getId())
      );
    }
}
