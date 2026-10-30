<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\LinkedInService;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpLinkedInProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * MCP bridge to LinkedInService (api-platform-integration).
 * Tokens stay in Config; never returned to the MCP client.
 */
final class McpLinkedInProvider implements McpLinkedInProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LinkedInService $linkedInService,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
    ) {
    }

    public function getStatus(int $companyId): array
    {
        $provider = $this->requireProvider($companyId);
        $status = $this->linkedInService->getConnectionStatus($provider);

        return array_merge(['company_id' => $companyId], $status);
    }

    public function createPost(int $companyId, string $text): array
    {
        $provider = $this->requireProvider($companyId);

        return $this->linkedInService->createMemberPost($provider, $text);
    }

    public function getProfile(int $companyId): array
    {
        $provider = $this->requireProvider($companyId);

        return $this->linkedInService->getProfile($provider);
    }

    public function listOrganizations(int $companyId): array
    {
        $provider = $this->requireProvider($companyId);

        return $this->linkedInService->listOrganizations($provider);
    }

    public function createOrganizationPost(int $companyId, string $organizationUrn, string $text): array
    {
        $provider = $this->requireProvider($companyId);

        return $this->linkedInService->createOrganizationPost($provider, $organizationUrn, $text);
    }

    public function getCapabilities(int $companyId): array
    {
        $provider = $this->requireProvider($companyId);

        return $this->linkedInService->getCapabilities($provider);
    }

    private function requireProvider(int $companyId): People
    {
        $this->assertCompanyAccess($companyId);
        $people = $this->em->find(People::class, $companyId);
        if (!$people instanceof People) {
            throw new \InvalidArgumentException('company not found');
        }

        return $people;
    }

    private function assertCompanyAccess(int $companyId): void
    {
        if ($companyId < 1) {
            throw new \InvalidArgumentException('company_id is required');
        }
        $allowed = $this->companyScopeProvider->listForCurrentUser();
        foreach ($allowed as $row) {
            if ((int) ($row['id'] ?? 0) === $companyId) {
                return;
            }
        }
        throw new \RuntimeException('Access denied to company_id ' . $companyId);
    }
}
