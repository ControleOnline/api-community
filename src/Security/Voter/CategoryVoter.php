<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\PeopleRoleService;
use App\Service\UserCompanyService;
use App\Entity\Category;
use ControleOnline\Entity\User;
use App\Entity\People;

class CategoryVoter extends Voter
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
  ) {
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

    if (!$subject instanceof Category) {
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
     * @var Category $category
     */
    $category = $subject;

    switch ($attribute) {
      case self::CREATE:
        return $this->canCreate($category, $user);
      case self::READ:
        return $this->canRead($category, $user);
      case self::EDIT:
        return $this->canEdit($category, $user);
      case self::DELETE:
        return $this->canDelete($category, $user);
    }

    throw new \LogicException('This code should not be reached!');
  }

  private function canCreate(Category $category, User $user)
  {
    return $this->isUserAdminInCompany($category, $user);
  }

  private function canRead(Category $category, User $user)
  {
    return $this->isUserAdminInCompany($category, $user);
  }

  private function canEdit(Category $category, User $user)
  {
    return $this->isUserAdminInCompany($category, $user);
  }

  private function canDelete(Category $category, User $user)
  {
    return $this->isUserAdminInCompany($category, $user);
  }

  private function isUserAdminInCompany(Category $category, User $user): bool
  {
    $roles = $this->roles->getAllRoles($user->getPeople());

    if ($category->getCompany() === null)
      return false;

    if (in_array('super', $roles) || in_array('franchisee', $roles) || in_array('salesman', $roles)) {
      return $this->company->isMyCompany($category->getCompany());
    }

    return false;
  }
}
