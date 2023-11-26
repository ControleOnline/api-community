<?php

namespace App\Security\Voter;

use App\Entity\MyContract;
use ControleOnline\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class MyContractVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE])) {
            return false;
        }

        if (!$subject instanceof MyContract) {
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
         * @var MyContract $contract
         */
        $contract = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($contract, $user);
            case self::READ:
                return $this->canRead  ($contract, $user);
            case self::EDIT:
                return $this->canEdit  ($contract, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(MyContract $contract, User $user)
    {
        return false;
    }

    private function canRead(MyContract $contract, User $user)
    {
        return false;
    }

    private function canEdit(MyContract $contract, User $user)
    {
        return $contract->getContractStatus() == 'Draft';
    }
}
