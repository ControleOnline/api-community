<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\CreateInvoice;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;

class CreateInvoiceVoter extends Voter
{
    const CREATE = 'create';

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
        if (!in_array($attribute, [self::CREATE])) {
            return false;
        }

        if (!$subject instanceof CreateInvoice) {
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
         * @var CreateInvoice $createInvoice
         */
        $createInvoice = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($createInvoice, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(CreateInvoice $createInvoice, User $user)
    {
      return $this->isUserAdminInCompany($createInvoice, $user);
    }

    private function isUserAdminInCompany(CreateInvoice $createInvoice, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user);

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        if (!empty($createInvoice->company)) {
          $company = $this->manager->getRepository(People::class)
            ->find($createInvoice->company);

          if ($company instanceof People) {
            return $this->company->isMyCompany($company);
          }
        }
      }

      return false;
    }
}
