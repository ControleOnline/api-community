<?php

namespace App\Service;

use ControleOnline\Entity\PeopleDomain;
use Doctrine\ORM\EntityManagerInterface;

final class DomainThemeBinding
{
    /** @return array{0: ?PeopleDomain, 1: bool} */
    public static function resolve(EntityManagerInterface $manager, string $host): array
    {
        $repository = $manager->getRepository(PeopleDomain::class);
        $criteria = ['domain' => $host];
        $domains = $repository->findBy($criteria, ['id' => 'ASC']);
        $first = $domains[0] ?? null;
        $companyId = $first?->getPeople()?->getId();
        foreach (array_slice($domains, 1) as $duplicate) {
            if ($duplicate->getPeople()?->getId() !== $companyId) {
                // Preserve the legacy read when the hostname spans tenants, but refuse writes.
                return [$repository->findOneBy($criteria), true];
            }
        }

        // A duplicate with no palette must not hide an existing themed binding.
        foreach ($domains as $domain) {
            if ($domain->getTheme()) return [$domain, false];
        }

        return [$first, false];
    }
}
