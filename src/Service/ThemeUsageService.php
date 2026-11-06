<?php

namespace App\Service;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;

/** Discover owning ORM references instead of assuming only people_domain uses themes. */
class ThemeUsageService
{
    public function __construct(private EntityManagerInterface $manager, private PeopleRoleService $roles) {}

    public function describe(Theme $theme, bool $global = false): array
    {
        $links = [];
        $hidden = 0;
        // Module metadata is loaded lazily in this app: getAllMetadata() alone omits PeopleDomain.
        $metadataByClass = [];
        foreach ($this->manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $metadataByClass[$metadata->getName()] = $metadata;
        }
        $metadataByClass[PeopleDomain::class] = $this->manager->getClassMetadata(PeopleDomain::class);
        $global = $global && $this->roles->isSuperAdmin();
        $companyIds = array_map(static fn ($company) => (int) $company->getId(), $this->roles->getAccessibleCompaniesForPeople());
        foreach ($metadataByClass as $metadata) {
            foreach ($metadata->getAssociationNames() as $field) {
                if (!$metadata->isAssociationWithSingleJoinColumn($field)
                    || $metadata->isAssociationInverseSide($field)
                    || !is_a($metadata->getAssociationTargetClass($field), Theme::class, true)) {
                    continue;
                }
                $references = $this->manager->getRepository($metadata->getName())->findBy([$field => $theme]);
                foreach ($references as $reference) {
                    if ($reference instanceof PeopleDomain && $reference->getPeople()
                        && ($global || in_array((int) $reference->getPeople()->getId(), $companyIds, true))) {
                        $links[] = ['type' => 'domain', 'id' => $reference->getId(),
                            'domain' => $reference->getDomain(), 'companyId' => $reference->getPeople()->getId(),
                            'companyName' => $reference->getPeople()->getAlias() ?: $reference->getPeople()->getName()];
                    } elseif ($global) {
                        $links[] = ['type' => $metadata->getName(), 'id' => method_exists($reference, 'getId') ? $reference->getId() : null];
                    } else {
                        ++$hidden;
                    }
                }
            }
        }
        return ['count' => count($links) + $hidden, 'links' => $links, 'hiddenCount' => $hidden];
    }
}
