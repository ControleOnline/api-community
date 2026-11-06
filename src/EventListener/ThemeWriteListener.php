<?php

namespace App\EventListener;

use App\Service\ThemeAccessService;
use ControleOnline\Entity\{PeopleDomain, Theme};
use Symfony\Component\HttpFoundation\RequestStack;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsDoctrineListener(event: Events::onFlush)]
final class ThemeWriteListener
{
    public function __construct(private ThemeAccessService $access, private PeopleRoleService $roles, private RequestStack $requests) {}
    public function onFlush(OnFlushEventArgs $event): void
    {
        if (!$this->requests->getMainRequest()) return;
        $unit = $event->getObjectManager()->getUnitOfWork();
        // Unowned catalog creation/deletion are global operations. Existing palette
        // edits require authority over every company affected by that shared theme.
        foreach ([...$unit->getScheduledEntityInsertions(), ...$unit->getScheduledEntityDeletions()] as $entity) {
            if ($entity instanceof Theme && !$this->roles->isSuperAdmin()) throw new AccessDeniedHttpException('A gestão global de temas exige superadministrador.');
            if ($entity instanceof PeopleDomain && $entity->getTheme() && in_array($entity, $unit->getScheduledEntityInsertions(), true)) $this->assertDomainTheme($entity);
        }
        foreach ($unit->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Theme && !$this->access->canEdit($entity)) throw new AccessDeniedHttpException('Sem permissão para editar este tema compartilhado.');
            if ($entity instanceof PeopleDomain && array_key_exists('theme', $unit->getEntityChangeSet($entity))) {
                $this->assertDomainTheme($entity);
            }
        }
    }
    private function assertDomainTheme(PeopleDomain $domain): void
    {
        // Both POST and updates must enforce the same existing company authority.
        if (!$domain->getPeople() || !$this->roles->canAdministerCompany($domain->getPeople())) throw new AccessDeniedHttpException('Sem permissão para alterar o tema deste domínio.');
        if ($domain->getTheme()) $this->access->assertRead($domain->getTheme());
    }
}
