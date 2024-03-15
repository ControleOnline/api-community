<?php

namespace App\Security\Voter;

use ControleOnline\Entity\MyContractPeople;
use ControleOnline\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class MyContractPeopleVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof MyContractPeople) {
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

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /**
         * @var MyContractPeople $contractPeople
         */
        $contractPeople = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($contractPeople, $user);
            case self::READ:
                return $this->canRead  ($contractPeople, $user);
            case self::EDIT:
                return $this->canEdit  ($contractPeople, $user);
            case self::DELETE:
                return $this->canDelete($contractPeople, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(MyContractPeople $contractPeople, User $user)
    {
        return $contractPeople->getContract()->getContractStatus() == 'Draft';
    }

    private function canRead(MyContractPeople $contractPeople, User $user)
    {
        return false;
    }

    private function canEdit(MyContractPeople $contractPeople, User $user)
    {
        return false;
    }

    private function canDelete(MyContractPeople $contractPeople, User $user)
    {
        return $contractPeople->getContract()->getContractStatus() == 'Draft';
    }
}
