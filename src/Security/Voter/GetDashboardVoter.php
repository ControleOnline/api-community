<?php

namespace App\Security\Voter;

use App\Resource\GetDashboard;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class GetDashboardVoter extends Voter
{
    const READ = 'read';

    public function __construct(Security $security, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->manager  = $manager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::READ])) {
            return false;
        }

        if (!$subject instanceof GetDashboard) {
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
         * @var GetDashboard $dashboard
         */
        $dashboard = $subject;

        switch ($attribute) {
          case self::READ  :
              return $this->canRead($dashboard, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canRead(GetDashboard $dashboard, User $user)
    {
        return true;
    }
}
