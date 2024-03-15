<?php

namespace App\Security\Voter;

use ControleOnline\Entity\DeliveryTax;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryTaxVoter extends Voter
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

        if (!$subject instanceof DeliveryTax) {
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
         * @var DeliveryTax $deliveryTax
         */
        $deliveryTax = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($deliveryTax, $user);
            case self::READ  :
                return $this->canRead  ($deliveryTax, $user);
            case self::EDIT  :
                return $this->canEdit  ($deliveryTax, $user);
            case self::DELETE:
                return $this->canDelete($deliveryTax, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(DeliveryTax $deliveryTax, User $user)
    {
        return true;
    }

    private function canRead(DeliveryTax $deliveryTax, User $user)
    {
        return true;
    }

    private function canEdit(DeliveryTax $deliveryTax, User $user)
    {
        return true;
    }

    private function canDelete(DeliveryTax $deliveryTax, User $user)
    {
        return true;
    }
}
