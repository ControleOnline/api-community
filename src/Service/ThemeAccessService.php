<?php

namespace App\Service;

use ControleOnline\Entity\{PeopleDomain, Theme};
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Reuse the same active company links as the company selector; themes have no owner column. */
class ThemeAccessService
{
    public function __construct(private EntityManagerInterface $manager, private PeopleRoleService $roles) {}

    public function companyThemeIds(): array
    {
        $companies = $this->roles->getAccessibleCompaniesForPeople();
        if ($companies === []) return [];
        $domains = $this->manager->getRepository(PeopleDomain::class)->findBy(['people' => $companies]);
        $ids = [];
        foreach ($domains as $domain) {
            if ($domain->getTheme()) $ids[] = $domain->getTheme()->getId();
        }
        return array_values(array_unique($ids));
    }

    public function assertRead(Theme $theme): void
    {
        if (!$this->roles->isSuperAdmin() && !in_array($theme->getId(), $this->companyThemeIds(), true)) {
            throw new AccessDeniedHttpException('Este tema não pertence aos domínios das suas empresas.');
        }
    }

    public function canEdit(Theme $theme): bool
    {
        if ($this->roles->isSuperAdmin()) return true;
        $domains = $this->manager->getRepository(PeopleDomain::class)->findBy(['theme' => $theme]);
        if ($domains === []) return false;
        // Editing a shared palette changes every domain using it, not only the selected company.
        foreach ($domains as $domain) {
            if (!$domain->getPeople() || !$this->roles->canAdministerCompany($domain->getPeople())) return false;
        }
        return true;
    }
}
