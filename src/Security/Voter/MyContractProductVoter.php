<?php

namespace App\Security\Voter;

use App\Entity\MyContractProduct;
use ControleOnline\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class MyContractProductVoter extends Voter
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

        if (!$subject instanceof MyContractProduct) {
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
         * @var MyContractProduct $contractProduct
         */
        $contractProduct = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($contractProduct, $user);
            case self::READ:
                return $this->canRead  ($contractProduct, $user);
            case self::EDIT:
                return $this->canEdit  ($contractProduct, $user);
            case self::DELETE:
                return $this->canDelete($contractProduct, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(MyContractProduct $contractProduct, User $user)
    {
        return $contractProduct->getContract()->getContractStatus() == 'Draft';
    }

    private function canRead(MyContractProduct $contractProduct, User $user)
    {
        return false;
    }

    private function canEdit(MyContractProduct $contractProduct, User $user)
    {
        return false;
    }

    private function canDelete(MyContractProduct $contractProduct, User $user)
    {
        return $contractProduct->getContract()->getContractStatus() == 'Draft';
    }
}
