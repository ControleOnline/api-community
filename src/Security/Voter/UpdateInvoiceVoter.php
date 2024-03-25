<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\UpdateInvoice;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class UpdateInvoiceVoter extends Voter
{
    const EDIT = 'edit';

    public function __construct(
      Security               $security,
      EntityManagerInterface $manager,
      PeopleRoleService      $roles,
      UserCompanyService     $company
    )
    {
        $this->security = $security;
        $this->manager  = $manager;
        $this->roles    = $roles;
        $this->company  = $company;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::EDIT])) {
            return false;
        }

        if (!$subject instanceof UpdateInvoice) {
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
         * @var UpdateInvoice $updateInvoice
         */
        $updateInvoice = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($updateInvoice, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(UpdateInvoice $updateInvoice, User $user)
    {
      return $this->isUserAdminInCompany($updateInvoice, $user);
    }

    private function isUserAdminInCompany(UpdateInvoice $updateInvoice, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        if (!empty($updateInvoice->company)) {
          $company = $this->manager->getRepository(People::class)
            ->find($updateInvoice->company);

          if ($company instanceof People) {
            return $this->company->isMyCompany($company);
          }
        }
      }

      return false;
    }
}
