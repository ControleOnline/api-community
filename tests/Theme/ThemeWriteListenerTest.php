<?php

use App\EventListener\ThemeWriteListener;
use App\Service\ThemeAccessService;
use ControleOnline\Entity\{People, PeopleDomain, Theme};
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\{EntityManagerInterface, UnitOfWork};
use Doctrine\ORM\Event\OnFlushEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ThemeWriteListenerTest extends TestCase
{
    private function listenerEvent(array $insertions, array $updates, bool $http = true): array
    {
        $access = $this->createMock(ThemeAccessService::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('isSuperAdmin')->willReturn(false);
        $unit = $this->createMock(UnitOfWork::class);
        $unit->method('getScheduledEntityInsertions')->willReturn($insertions);
        $unit->method('getScheduledEntityUpdates')->willReturn($updates);
        $unit->method('getScheduledEntityDeletions')->willReturn([]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($unit);
        $requests = new RequestStack();if ($http) $requests->push(new Request());
        return [new ThemeWriteListener($access, $roles, $requests), new OnFlushEventArgs($manager), $access, $roles, $unit];
    }
    public function testOriginalThemeWriteIsDeniedWithoutAuthorityOverAllReferences(): void
    {
        [$listener, $event, $access] = $this->listenerEvent([], [new Theme()]);
        $access->expects(self::once())->method('canEdit')->willReturn(false);
        $this->expectException(AccessDeniedHttpException::class);$listener->onFlush($event);
    }
    public function testUnownedGlobalCreationIsDeniedToNonSuper(): void
    {
        [$listener, $event] = $this->listenerEvent([new Theme()], []);
        $this->expectException(AccessDeniedHttpException::class);$listener->onFlush($event);
    }
    public function testOriginalDomainWriteCannotRebindAnotherCompany(): void
    {
        $domain = new PeopleDomain();$domain->setPeople(new People());$domain->setTheme(new Theme());
        [$listener, $event, $access, $roles, $unit] = $this->listenerEvent([], [$domain]);
        $roles->method('canAdministerCompany')->willReturn(false);
        $unit->method('getEntityChangeSet')->willReturn(['theme' => [null, $domain->getTheme()]]);
        $access->expects(self::never())->method('assertRead');
        $this->expectException(AccessDeniedHttpException::class);$listener->onFlush($event);
    }
    public function testUnrelatedDomainUpdatesAndInternalConsoleFlowsArePreserved(): void
    {
        $domain = new PeopleDomain();
        [$listener, $event, $access, $roles, $unit] = $this->listenerEvent([], [$domain]);
        $unit->method('getEntityChangeSet')->willReturn(['domain' => ['old', 'new']]);
        $roles->expects(self::never())->method('canAdministerCompany');
        $listener->onFlush($event);
        [$consoleListener, $consoleEvent, $consoleAccess] = $this->listenerEvent([new Theme()], [], false);
        $consoleAccess->expects(self::never())->method('canEdit');
        $consoleListener->onFlush($consoleEvent);
        self::assertTrue(true);
    }
    public function testNewDomainCannotBindThemeWithoutCompanyAdministration(): void
    {
        $domain = new PeopleDomain();$domain->setPeople(new People());$domain->setTheme(new Theme());
        [$listener, $event, $access, $roles] = $this->listenerEvent([$domain], []);
        $roles->expects(self::once())->method('canAdministerCompany')->with($domain->getPeople())->willReturn(false);
        $access->expects(self::never())->method('assertRead');
        $this->expectException(AccessDeniedHttpException::class);$listener->onFlush($event);
    }
    public function testNewDomainBindingAlsoRequiresReadableTheme(): void
    {
        $domain = new PeopleDomain();$domain->setPeople(new People());$domain->setTheme(new Theme());
        [$listener, $event, $access, $roles] = $this->listenerEvent([$domain], []);
        $roles->expects(self::once())->method('canAdministerCompany')->with($domain->getPeople())->willReturn(true);
        $access->expects(self::once())->method('assertRead')->with($domain->getTheme());
        $listener->onFlush($event);
    }

}
