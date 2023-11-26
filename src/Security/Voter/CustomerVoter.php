<?php

namespace App\Security\Voter;

use App\Entity\People;
use ControleOnline\Entity\User;
use App\Entity\PeopleClient;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;

class CustomerVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

    public function __construct(Security $security, EntityManagerInterface $manager, PeopleRoleService $roles)
    {
        $this->security    = $security;
        $this->manager     = $manager;
        $this->peopleRoles = $roles;
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

        if (
            $this->peopleRoles->isSuperAdmin($user->getPeople()) ||
            $this->peopleRoles->isFranchisee($user->getPeople())
        ) {
            return true;
        }

        /**
         * @var People $customer
         */
        $customer = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($customer, $user);
            case self::READ:
                return $this->canRead($customer, $user);
            case self::EDIT:
                return $this->canEdit($customer, $user);
            case self::DELETE:
                return $this->canDelete($customer, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(People $customer, User $user)
    {
        return true;
    }

    private function canRead(People $customer, User $user)
    {
        return $this->isMyCustomer($customer, $user);
    }

    private function canEdit(People $customer, User $user)
    {
        return $this->isMyCustomer($customer, $user);
    }

    private function canDelete(People $customer, User $user)
    {
        return $this->isMyCustomer($customer, $user);
    }

    private function isMyCustomer(People $customer, User $user): bool
    {
        $people = $this->manager->getRepository(People::class)->find($customer->getId());

        return $this->manager->getRepository(PeopleClient::class)
            ->peopleIsMyClient($user->getPeople(), $people);
    }
}
