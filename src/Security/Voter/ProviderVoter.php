<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Entity\Provider;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class ProviderVoter extends Voter
{


    private $security;
    private  $manager;
    private  $roles;

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

        if (!$subject instanceof Provider) {
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
         * @var Provider $provider
         */
        $provider = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($provider, $user);
            case self::READ:
                return $this->canRead($provider, $user);
            case self::EDIT:
                return $this->canEdit($provider, $user);
            case self::DELETE:
                return $this->canDelete($provider, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(Provider $provider, User $user)
    {
        $roles = $this->roles->getAllRoles($user);

        if (in_array('super', $roles) || in_array('franchisee', $roles)) {
            return true;
        }

        return false;
    }

    private function canRead(Provider $provider, User $user)
    {
        $roles = $this->roles->getAllRoles($user);

        if (in_array('super', $roles) || in_array('franchisee', $roles)) {
            return true;
        }

        return false;
    }

    private function canEdit(Provider $provider, User $user)
    {
        $roles = $this->roles->getAllRoles($user);

        if (in_array('super', $roles) || in_array('franchisee', $roles)) {
            return true;
        }

        return false;
    }

    private function canDelete(Provider $provider, User $user)
    {
        $roles = $this->roles->getAllRoles($user);

        if (in_array('super', $roles) || in_array('franchisee', $roles)) {
            return true;
        }

        return false;
    }
}
