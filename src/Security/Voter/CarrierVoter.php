<?php

namespace App\Security\Voter;

use ControleOnline\Entity\Carrier;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class CarrierVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

    public function __construct(Security $security, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->manager  = $manager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Carrier) {
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
         * @var Carrier $carrier
         */
        $carrier = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($carrier, $user);
            case self::READ  :
                return $this->canRead  ($carrier, $user);
            case self::EDIT  :
                return $this->canEdit  ($carrier, $user);
            case self::DELETE:
                return $this->canDelete($carrier, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(Carrier $carrier, User $user)
    {
        return true;
    }

    private function canRead(Carrier $carrier, User $user)
    {
        return true;
    }

    private function canEdit(Carrier $carrier, User $user)
    {
        return true;
    }

    private function canDelete(Carrier $carrier, User $user)
    {
        return true;
    }
}
