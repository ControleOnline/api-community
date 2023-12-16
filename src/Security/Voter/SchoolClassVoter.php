<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;
use ControleOnline\Entity\SchoolClass;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class SchoolClassVoter extends Voter
{
    const READ             = 'read';
    const EDIT             = 'edit';
    const CREATE           = 'create';
    const DELETE           = 'delete';
    const EDIT_DATE_STATUS = 'edit_date_status';

    public function __construct(Security $security, EntityManagerInterface $manager, PeopleRoleService $roles)
    {
        $this->security = $security;
        $this->manager  = $manager;
        $this->roles    = $roles;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE, self::EDIT_DATE_STATUS])) {
            return false;
        }

        if (!$subject instanceof SchoolClass) {
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
         * @var SchoolClass $schoolClass
         */
        $schoolClass = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($schoolClass, $user);
            case self::READ  :
                return $this->canRead  ($schoolClass, $user);
            case self::EDIT  :
                return $this->canEdit  ($schoolClass, $user);
            case self::DELETE:
                return $this->canDelete($schoolClass, $user);
            case self::EDIT_DATE_STATUS:
                return $this->canEditDateStatus($schoolClass, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(SchoolClass $schoolClass, User $user)
    {
        return false;
    }

    private function canRead(SchoolClass $schoolClass, User $user)
    {
        return false;
    }

    private function canEdit(SchoolClass $schoolClass, User $user)
    {
        return false;
    }

    private function canDelete(SchoolClass $schoolClass, User $user)
    {
        return false;
    }

    private function canEditDateStatus(SchoolClass $schoolClass, User $user)
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if (in_array('super', $roles) || in_array('franchisee', $roles) || in_array('salesman', $roles)) {
          return true;
      }

      return false;
    }
}
