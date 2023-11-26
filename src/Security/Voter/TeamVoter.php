<?php

namespace App\Security\Voter;

use App\Entity\Team;
use ControleOnline\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class TeamVoter extends Voter
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

        if (!$subject instanceof Team) {
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
         * @var Team $team
         */
        $team = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($team, $user);
            case self::READ:
                return $this->canRead  ($team, $user);
            case self::EDIT:
                return $this->canEdit  ($team, $user);
            case self::DELETE:
                return $this->canDelete($team, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(Team $team, User $user)
    {
        return false;
    }

    private function canRead(Team $team, User $user)
    {
        return false;
    }

    private function canEdit(Team $team, User $user)
    {
        return $team->getContract()->getContractStatus() == 'Draft';
    }

    private function canDelete(Team $team, User $user)
    {
        return $team->getContract()->getContractStatus() == 'Draft';
    }
}
