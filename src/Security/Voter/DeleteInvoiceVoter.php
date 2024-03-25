<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Resource\DeleteInvoice;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Invoice;

class DeleteInvoiceVoter extends Voter
{
    const DELETE = 'delete';

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
        if (!in_array($attribute, [self::DELETE])) {
            return false;
        }

        if (!$subject instanceof DeleteInvoice) {
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
         * @var DeleteInvoice $deleteInvoice
         */
        $deleteInvoice = $subject;

        switch ($attribute) {
            case self::DELETE:
                return $this->canDelete($deleteInvoice, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canDelete(DeleteInvoice $deleteInvoice, User $user)
    {
      $invoice = $this->manager->find(Invoice::class, $deleteInvoice->id);
      if ($invoice === null) {
        return false;
      }

      if ($invoice->isPaid()) {
        return false;
      }

      return $this->isUserAdminInCompany($deleteInvoice, $user);
    }

    private function isUserAdminInCompany(DeleteInvoice $deleteInvoice, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        if (!empty($deleteInvoice->company)) {
          $company = $this->manager->getRepository(People::class)
            ->find($deleteInvoice->company);

          if ($company instanceof People) {
            return $this->company->isMyCompany($company);
          }
        }
      }

      return false;
    }
}
