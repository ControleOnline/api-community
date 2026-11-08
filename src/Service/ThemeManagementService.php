<?php

namespace App\Service;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThemeManagementService
{
    public function __construct(private EntityManagerInterface $manager, private PeopleRoleService $roles, private ThemeUsageService $usage, private ThemeAccessService $access) {}

    public function domain(string $host): ?array
    {
        [$domain, $ambiguous] = DomainThemeBinding::resolve($this->manager, $host);
        $company = $domain?->getPeople();
        if (!$company || (!$this->roles->isSuperAdmin() && !$this->roles->canAccessCompany($company))) return null;

        return [
            'id' => $domain->getId(),
            'domain' => $domain->getDomain(),
            'themeId' => $domain->getTheme()?->getId(),
            'canApply' => !$ambiguous && $this->roles->canAdministerCompany($company),
            'applyBlockedReason' => $ambiguous ? 'ambiguous-company' : null,
        ];
    }

    public function catalog(string $domainName, int $page, bool $global = false): array
    {
        $super = $this->roles->isSuperAdmin();
        if ($global && !$super) throw new AccessDeniedHttpException('A gestão global exige superadministrador.');
        $domain = $this->domain($domainName);
        $ids = $global ? [] : $this->access->companyThemeIds();
        if (!$global && isset($domain['themeId'])) $ids[] = $domain['themeId'];
        $themes = !$global && $ids === [] ? [] : $this->manager->getRepository(Theme::class)->findBy(
            $global ? [] : ['id' => array_values(array_unique($ids))], ['id' => 'ASC'], 51, ($page - 1) * 50
        );
        $more = count($themes) > 50;
        $items = [];
        foreach (array_slice($themes, 0, 50) as $theme) {
            $usage = $this->usage->describe($theme, $global);
            $items[] = ['id' => $theme->getId(), 'theme' => $theme->getTheme(), 'usage' => $usage,
                'canEdit' => $this->access->canEdit($theme),
                'canDelete' => $global && $super && $usage['count'] === 0];
        }
        return ['items' => $items, 'nextPage' => $more ? $page + 1 : null,
            'domain' => $domain,
            'canDeleteUnused' => $global && $super, 'canManageGlobal' => $super, 'scope' => $global ? 'global' : 'companies'];
    }

    public function apply(int $domainId, int $themeId, ?int $expectedThemeId): array
    {
        return $this->manager->wrapInTransaction(function () use ($domainId, $themeId, $expectedThemeId) {
            // Lock the target theme first, consistently with deletion, then re-read the domain.
            $theme = $this->manager->find(Theme::class, $themeId, LockMode::PESSIMISTIC_WRITE);
            if (!$theme) throw new NotFoundHttpException('Tema não encontrado.');
            $this->access->assertRead($theme);
            $domain = $this->manager->find(PeopleDomain::class, $domainId, LockMode::PESSIMISTIC_WRITE);
            if (!$domain) throw new NotFoundHttpException('Domínio não encontrado.');
            $this->manager->refresh($domain);
            if (!$domain->getPeople() || !$this->roles->canAdministerCompany($domain->getPeople())) {
                throw new AccessDeniedHttpException('Sem permissão para alterar o tema deste domínio.');
            }
            [$binding, $ambiguous] = DomainThemeBinding::resolve($this->manager, $domain->getDomain());
            if ($ambiguous || $binding?->getId() !== $domainId) {
                throw new ConflictHttpException('O registro ativo do domínio mudou ou está ambíguo. Atualize a lista.');
            }
            if ($domain->getTheme()?->getId() !== $expectedThemeId) {
                throw new ConflictHttpException('O tema do domínio mudou. Atualize a lista antes de continuar.');
            }
            $domain->setTheme($theme);
            $this->manager->flush();
            return ['domainId' => $domainId, 'themeId' => $themeId];
        });
    }

    public function delete(int $themeId): void
    {
        // Themes have no owner column: deletion affects the shared catalog.
        if (!$this->roles->isSuperAdmin()) throw new AccessDeniedHttpException('A exclusão de temas exige superadministrador.');
        try {
            $this->manager->wrapInTransaction(function () use ($themeId) {
                $theme = $this->manager->find(Theme::class, $themeId, LockMode::PESSIMISTIC_WRITE);
                if (!$theme) throw new NotFoundHttpException('Tema não encontrado.');
                if ($this->usage->describe($theme)['count'] !== 0) {
                    throw new ConflictHttpException('O tema está em uso. Consulte seus vínculos.');
                }
                $this->manager->remove($theme);
                $this->manager->flush();
            });
        } catch (ForeignKeyConstraintViolationException $error) {
            // Database constraints also cover concurrent bindings and legacy references.
            throw new ConflictHttpException('O tema possui vínculos e não pode ser excluído.', $error);
        }
    }
}
