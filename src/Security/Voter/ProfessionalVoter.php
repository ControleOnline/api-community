<?php

namespace App\Security\Voter;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\PeopleRoleService;

class ProfessionalVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

    public function __construct(Security $security, EntityManagerInterface $manager, PeopleRoleService $roles)
    {
        $this->security = $security;
        $this->manager  = $manager;
        $this->roles    = $roles;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof People) {
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

        $roles = $this->roles->getAllRoles($user->getPeople());
        if (!in_array('super', $roles) && !in_array('franchisee', $roles) && !in_array('salesman', $roles)) {
            return false;
        }

        /**
         * @var People $professional
         */
        $professional = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($professional, $user);
            case self::READ  :
                return $this->canRead  ($professional, $user);
            case self::EDIT  :
                return $this->canEdit  ($professional, $user);
            case self::DELETE:
                return $this->canDelete($professional, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(People $professional, User $user)
    {
        return true;
    }

    private function canRead(People $professional, User $user)
    {
        return true;
    }

    private function canEdit(People $professional, User $user)
    {
        return true;
    }

    private function canDelete(People $professional, User $user)
    {
        return true;
    }
}
