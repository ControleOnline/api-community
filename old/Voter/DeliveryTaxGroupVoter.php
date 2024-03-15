<?php

namespace App\Security\Voter;

use ControleOnline\Entity\DeliveryTaxGroup;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryTaxGroupVoter extends Voter
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

        if (!$subject instanceof DeliveryTaxGroup) {
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
         * @var DeliveryTaxGroup $deliveryTaxGroup
         */
        $deliveryTaxGroup = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($deliveryTaxGroup, $user);
            case self::READ  :
                return $this->canRead  ($deliveryTaxGroup, $user);
            case self::EDIT  :
                return $this->canEdit  ($deliveryTaxGroup, $user);
            case self::DELETE:
                return $this->canDelete($deliveryTaxGroup, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(DeliveryTaxGroup $deliveryTaxGroup, User $user)
    {
        return true;
    }

    private function canRead(DeliveryTaxGroup $deliveryTaxGroup, User $user)
    {
        return true;
    }

    private function canEdit(DeliveryTaxGroup $deliveryTaxGroup, User $user)
    {
        return true;
    }

    private function canDelete(DeliveryTaxGroup $deliveryTaxGroup, User $user)
    {
        return true;
    }
}
