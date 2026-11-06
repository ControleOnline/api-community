<?php

use App\Doctrine\Extension\ThemeSecurityExtension;
use App\Service\ThemeAccessService;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Entity\{People, PeopleDomain, Theme};
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\{EntityManagerInterface, EntityRepository, QueryBuilder};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ThemeAccessTest extends TestCase
{
    private function theme(int $id): Theme
    {
        $theme = new Theme();(new ReflectionProperty(Theme::class, 'id'))->setValue($theme, $id);return $theme;
    }
    private function domain(Theme $theme, People $company): PeopleDomain
    {
        $domain = new PeopleDomain();$domain->setTheme($theme);$domain->setPeople($company);return $domain;
    }
    public function testCompanyScopeFollowsActiveCompanyLinksAndDeduplicatesSharedThemes(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $company = new People();$theme = $this->theme(5);
        $roles->method('getAccessibleCompaniesForPeople')->willReturn([$company]);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findBy')->with(['people' => [$company]])
            ->willReturn([$this->domain($theme, $company), $this->domain($theme, $company)]);
        $manager->method('getRepository')->with(PeopleDomain::class)->willReturn($repository);
        self::assertSame([5], (new ThemeAccessService($manager, $roles))->companyThemeIds());
    }
    public function testUnboundThemesAreNotReadableByGuessingTheirId(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('isSuperAdmin')->willReturn(false);
        $roles->method('getAccessibleCompaniesForPeople')->willReturn([]);
        $manager->expects(self::never())->method('getRepository');
        $this->expectException(AccessDeniedHttpException::class);
        (new ThemeAccessService($manager, $roles))->assertRead($this->theme(6));
    }
    public function testSharedPaletteCannotBeEditedThroughOnlyOneCompanyPermission(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $roles = $this->createMock(PeopleRoleService::class);
        $own = new People();$other = new People();$theme = $this->theme(5);
        $roles->method('isSuperAdmin')->willReturn(false);
        $roles->method('canAdministerCompany')->willReturnCallback(fn ($company) => $company === $own);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([$this->domain($theme, $own), $this->domain($theme, $other)]);
        $manager->method('getRepository')->willReturn($repository);
        self::assertFalse((new ThemeAccessService($manager, $roles))->canEdit($theme));
    }
    public function testOriginalThemeCollectionAndItemQueriesEnforceCompanyFilterButNotPublicCss(): void
    {
        $access = $this->createMock(ThemeAccessService::class);
        $access->method('companyThemeIds')->willReturn([5, 11]);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('isSuperAdmin')->willReturn(false);
        $manager = $this->createMock(EntityManagerInterface::class);
        $query = new QueryBuilder($manager);$query->select('t')->from(Theme::class, 't');
        $extension = new ThemeSecurityExtension($access, $roles);
        $extension->applyToItem($query, new QueryNameGenerator(), Theme::class, ['id' => 6]);
        self::assertStringContainsString('t.id IN', $query->getDQL());
        self::assertSame([5, 11], $query->getParameters()->first()->getValue());
        $public = new QueryBuilder($manager);$public->select('t')->from(Theme::class, 't');
        $extension->applyToCollection($public, new QueryNameGenerator(), Theme::class, new GetCollection(uriTemplate: '/themes-colors.css'));
        self::assertStringNotContainsString('WHERE', $public->getDQL());
    }
}
