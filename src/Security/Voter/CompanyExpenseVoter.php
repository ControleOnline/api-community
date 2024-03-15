<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;
use ControleOnline\Entity\CompanyExpense;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use App\Service\UserCompanyService;

class CompanyExpenseVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';
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
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof CompanyExpense) {
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
         * @var CompanyExpense $companyExpense
         */
        $companyExpense = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($companyExpense, $user);
            case self::READ  :
                return $this->canRead  ($companyExpense, $user);
            case self::EDIT  :
                return $this->canEdit  ($companyExpense, $user);
            case self::DELETE:
                return $this->canDelete($companyExpense, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(CompanyExpense $companyExpense, User $user)
    {
      return $this->isUserAdminInCompany($companyExpense, $user);
    }

    private function canRead(CompanyExpense $companyExpense, User $user)
    {
      return $this->isUserAdminInCompany($companyExpense, $user);
    }

    private function canEdit(CompanyExpense $companyExpense, User $user)
    {
      return $this->isUserAdminInCompany($companyExpense, $user);
    }

    private function canDelete(CompanyExpense $companyExpense, User $user)
    {
      return $this->isUserAdminInCompany($companyExpense, $user);
    }

    private function isUserAdminInCompany(CompanyExpense $category, User $user): bool
    {
      $roles = $this->roles->getAllRoles($user->getPeople());

      if ($category->getCompany() === null)
        return false;

      if (in_array('super', $roles) || in_array('franchisee', $roles)) {
        return $this->company->isMyCompany($category->getCompany());
      }

      return false;
    }
}
