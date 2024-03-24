<?php

namespace App\Security\Voter;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class PeopleVoter extends Voter
{
    const READ   = 'read';
    const EDIT   = 'edit';
    const CREATE = 'create';

    public function __construct(Security $security, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->manager  = $manager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ, self::EDIT, self::CREATE])) {
            return false;
        }

        if (!$subject instanceof People) {
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
         * @var People $people
         */
        $people = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($people, $user);
            case self::READ:
                return $this->canRead  ($people, $user);
            case self::EDIT:
                return $this->canEdit  ($people, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canCreate(People $people, User $user)
    {
        return false;
    }

    private function canRead(People $people, User $user)
    {
        return false;
    }

    private function canEdit(People $people, User $user)
    {
        if ($user->getPeople() === $people)
            return true;

        // allowed if is my company

        if (!$user->getPeople()->getPeopleCompany()->isEmpty()) {
            $isMyCompany = $user->getPeople()->getPeopleCompany()->exists(
                function ($key, $element) use ($people) {
                    return $element->getCompany() === $people;
                }
            );

            if ($isMyCompany)
                return true;
        }

        // allowed if is my employee

        if (!$user->getPeople()->getCompany()->isEmpty()) {
            $isMyEmployee = $user->getPeople()->getCompany()->exists(
                function ($key, $element) use ($people) {
                    return $element->getCompany() === $people;
                }
            );

            if ($isMyEmployee)
                return true;
        }

        // allowed if is my client

        if ($this->manager->getRepository(PeopleClient::class)->peopleIsMyClient($user->getPeople(), $people))
            return true;

        return false;
    }
}
