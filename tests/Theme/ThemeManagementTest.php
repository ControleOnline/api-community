<?php

use App\Controller\ThemeManagementController;
use App\Service\ThemeManagementService;
use App\Service\ThemeUsageService;
use App\Service\ThemeAccessService;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ThemeManagementTest extends TestCase
{
    private function theme(int $id): Theme
    {
        $theme = new Theme();
        (new ReflectionProperty(Theme::class, 'id'))->setValue($theme, $id);
        $theme->setTheme('Tema '.$id);
        return $theme;
    }

    private function domain(Theme $theme): PeopleDomain
    {
        $domain = new PeopleDomain();
        (new ReflectionProperty(PeopleDomain::class, 'id'))->setValue($domain, 7);
        $domain->setDomain('local.example');
        $domain->setPeople(new People());
        $domain->setTheme($theme);
        return $domain;
    }

    private function setupService(bool $super = true, int $usageCount = 0): array
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('wrapInTransaction')->willReturnCallback(fn ($callback) => $callback());
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('isSuperAdmin')->willReturn($super);
        $usage = $this->createMock(ThemeUsageService::class);
        $usage->method('describe')->willReturn(['count' => $usageCount, 'links' => [], 'hiddenCount' => $usageCount]);
        $access = $this->createMock(ThemeAccessService::class);
        $access->method('companyThemeIds')->willReturn(range(51, 101));
        return [new ThemeManagementService($manager, $roles, $usage, $access), $manager, $roles];
    }

    public function testDomainCatalogAndCssUseTheSameOrderedBinding(): void
    {
        [$service, $manager] = $this->setupService();
        $first = $this->domain($this->theme(11));
        $second = $this->domain($this->theme(5));
        (new ReflectionProperty(PeopleDomain::class, 'id'))->setValue($second, 12);
        $second->setPeople($first->getPeople());
        $domains = $this->createMock(EntityRepository::class);
        $domains->expects(self::exactly(3))->method('findBy')->with(['domain' => 'local.example'], ['id' => 'ASC'])
            ->willReturn([$first, $second]);
        $manager->method('getRepository')->with(PeopleDomain::class)->willReturn($domains);
        self::assertSame(7, $service->domain('local.example')['id']);

        $first->getTheme()->setColors(['cardBackground' => '#abcdef']);
        $host = $this->createMock(\ControleOnline\Service\DomainService::class);
        $host->method('getDomain')->willReturn('local.example');
        $response = (new \App\Controller\DomainThemeColorsAction($manager, $host))(new Request());
        self::assertStringContainsString('--cardBackground: #abcdef', $response->getContent());

        $first->setTheme(null);
        self::assertSame(12, $service->domain('local.example')['id']);
    }

    public function testCrossCompanyDuplicatesBlockTheWrite(): void
    {
        [$service, $manager, $roles] = $this->setupService();
        $theme = $this->theme(5);
        $first = $this->domain($this->theme(11));
        $second = $this->domain($theme);
        (new ReflectionProperty(People::class, 'id'))->setValue($first->getPeople(), 1);
        (new ReflectionProperty(People::class, 'id'))->setValue($second->getPeople(), 2);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$first, $second]);
        $repository->method('findOneBy')->willReturn($first);
        $manager->method('getRepository')->willReturn($repository);
        $manager->method('find')->willReturnCallback(fn ($type) => $type === Theme::class ? $theme : $first);
        $roles->method('canAdministerCompany')->willReturn(true);

        self::assertFalse($service->domain('local.example')['canApply']);
        $manager->expects(self::never())->method('flush');
        $this->expectException(ConflictHttpException::class);
        $service->apply(7, 5, 11);
    }

    public function testDeleteRequiresSuperBeforeLookingUpATheme(): void
    {
        [$service, $manager] = $this->setupService(false);
        $manager->expects(self::never())->method('find');
        $this->expectException(AccessDeniedHttpException::class);
        $service->delete(5);
    }

    public function testDeleteRefusesAnyReferenceIncludingHiddenOnes(): void
    {
        [$service, $manager] = $this->setupService(true, 1);
        $manager->method('find')->willReturn($this->theme(5));
        $manager->expects(self::never())->method('remove');
        $this->expectException(ConflictHttpException::class);
        $service->delete(5);
    }

    public function testDeleteUnboundThemeOnlyRemovesTheRequestedEntity(): void
    {
        [$service, $manager] = $this->setupService();
        $theme = $this->theme(5);
        $manager->method('find')->willReturn($theme);
        $manager->expects(self::once())->method('remove')->with($theme);
        $manager->expects(self::once())->method('flush');
        $service->delete(5);
    }

    public function testApplyUsesDomainCompanyAuthorityNotSelectedCompany(): void
    {
        [$service, $manager, $roles] = $this->setupService();
        $domain = $this->domain($this->theme(11));
        $manager->method('find')->willReturnCallback(fn ($type) => $type === Theme::class ? $this->theme(5) : $domain);
        $roles->expects(self::once())->method('canAdministerCompany')->with($domain->getPeople())->willReturn(false);
        $manager->expects(self::never())->method('flush');
        $this->expectException(AccessDeniedHttpException::class);
        $service->apply(7, 5, 11);
    }

    public function testApplyRejectsChangedDomainBinding(): void
    {
        [$service, $manager, $roles] = $this->setupService();
        $domain = $this->domain($this->theme(12));
        $manager->method('find')->willReturnCallback(fn ($type) => $type === Theme::class ? $this->theme(5) : $domain);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain]);
        $manager->method('getRepository')->willReturn($repository);
        $roles->method('canAdministerCompany')->willReturn(true);
        $manager->expects(self::never())->method('flush');
        $this->expectException(ConflictHttpException::class);
        $service->apply(7, 5, 11);
    }

    public function testApplyOnlyChangesThemeLeavingPeopleAndDomainUntouched(): void
    {
        [$service, $manager, $roles] = $this->setupService();
        $theme = $this->theme(5);
        $domain = $this->domain($this->theme(11));
        $company = $domain->getPeople();
        $manager->method('find')->willReturnCallback(fn ($type) => $type === Theme::class ? $theme : $domain);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain]);
        $manager->method('getRepository')->willReturn($repository);
        $roles->method('canAdministerCompany')->willReturn(true);
        $manager->expects(self::once())->method('refresh')->with($domain);
        $manager->expects(self::once())->method('flush');
        self::assertSame(['domainId' => 7, 'themeId' => 5], $service->apply(7, 5, 11));
        self::assertSame($theme, $domain->getTheme());
        self::assertSame($company, $domain->getPeople());
        self::assertSame('local.example', $domain->getDomain());
    }

    public function testUsageCountsInaccessibleDomainsWithoutDisclosingThem(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAdministerCompany')->willReturn(false);
        $roles->method('isSuperAdmin')->willReturn(false);
        $metadata = new ClassMetadata(PeopleDomain::class);
        $metadata->mapManyToOne(['fieldName' => 'theme', 'targetEntity' => Theme::class, 'joinColumns' => [['name' => 'theme_id', 'referencedColumnName' => 'id']]]);
        $factory = $this->createMock(ClassMetadataFactory::class);
        // Reproduce production's lazy module discovery: no PeopleDomain in enumeration.
        $factory->method('getAllMetadata')->willReturn([]);
        $manager->method('getMetadataFactory')->willReturn($factory);
        $manager->method('getClassMetadata')->with(PeopleDomain::class)->willReturn($metadata);
        $theme = $this->theme(5);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->with(['theme' => $theme])->willReturn([$this->domain($theme)]);
        $manager->method('getRepository')->willReturn($repository);
        self::assertSame(['count' => 1, 'links' => [], 'hiddenCount' => 1], (new ThemeUsageService($manager, $roles))->describe($theme));
    }

    public function testCatalogIsPaginatedAndReportsUsageWithoutGrantingDeleteToAnOwner(): void
    {
        [$service, $manager] = $this->setupService(false, 1);
        $domainRepository = $this->createMock(EntityRepository::class);
        $domainRepository->method('findBy')->willReturn([]);
        $themeRepository = $this->createMock(EntityRepository::class);
        $themeRepository->expects(self::once())->method('findBy')->with(['id' => range(51, 101)], ['id' => 'ASC'], 51, 50)
            ->willReturn(array_map(fn ($id) => $this->theme($id), range(51, 101)));
        $manager->method('getRepository')->willReturnCallback(fn ($type) => $type === Theme::class ? $themeRepository : $domainRepository);
        $result = $service->catalog('unavailable.example', 2);
        self::assertCount(50, $result['items']);
        self::assertSame(3, $result['nextPage']);
        self::assertNull($result['domain']);
        self::assertFalse($result['canDeleteUnused']);
        self::assertFalse($result['items'][0]['canDelete']);
    }

    public function testForeignKeyRaceIsReportedAsConflict(): void
    {
        [$service, $manager] = $this->setupService();
        $manager->method('find')->willReturn($this->theme(5));
        $driverError = new class('Referenced theme', '23000') extends \Doctrine\DBAL\Driver\AbstractException {};
        $manager->method('flush')->willThrowException(new \Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException($driverError, null));
        $this->expectException(ConflictHttpException::class);
        $service->delete(5);
    }

    public function testGlobalCatalogIsDeniedToNonSuperBeforeAnyRead(): void
    {
        [$service, $manager] = $this->setupService(false);
        $manager->expects(self::never())->method('getRepository');
        $this->expectException(AccessDeniedHttpException::class);
        $service->catalog('local.example', 1, true);
    }

    public function testEmptyCompanyAccessDoesNotFallBackToAllThemesEvenForSuper(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('isSuperAdmin')->willReturn(true);
        $access = $this->createMock(ThemeAccessService::class);
        $access->method('companyThemeIds')->willReturn([]);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([]);
        $manager->expects(self::once())->method('getRepository')->with(PeopleDomain::class)->willReturn($repository);
        $service = new ThemeManagementService($manager, $roles, $this->createMock(ThemeUsageService::class), $access);
        $result = $service->catalog('local.example', 1);
        self::assertSame([], $result['items']);
        self::assertTrue($result['canManageGlobal']);
        self::assertFalse($result['canDeleteUnused']);
        self::assertSame('companies', $result['scope']);
    }

    public function testGlobalCatalogExplicitlyAllowsSuperToListUnboundThemes(): void
    {
        [$service, $manager] = $this->setupService();
        $domains = $this->createMock(EntityRepository::class);
        $domains->method('findBy')->willReturn([]);
        $themes = $this->createMock(EntityRepository::class);
        $themes->expects(self::once())->method('findBy')->with([], ['id' => 'ASC'], 51, 0)->willReturn([$this->theme(5)]);
        $manager->method('getRepository')->willReturnCallback(fn ($type) => $type === Theme::class ? $themes : $domains);
        $result = $service->catalog('local.example', 1, true);
        self::assertTrue($result['items'][0]['canDelete']);
        self::assertSame('global', $result['scope']);
    }

    public function testHttpWriteRequiresExplicitPreviousBindingAndHumanRole(): void
    {
        $attribute = (new ReflectionClass(ThemeManagementController::class))->getAttributes(IsGranted::class)[0]->newInstance();
        self::assertSame('ROLE_HUMAN', $attribute->attribute);
        $service = $this->createMock(ThemeManagementService::class);
        $service->expects(self::never())->method('apply');
        $this->expectException(BadRequestHttpException::class);
        (new ThemeManagementController($service))->apply(7, new Request(content: '{"themeId":5}'));
    }
}
