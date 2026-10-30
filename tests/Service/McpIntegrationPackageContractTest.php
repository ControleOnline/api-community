<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;

final class McpIntegrationPackageContractTest extends TestCase
{
    public function testInstalledIntegrationProvidersHaveTheirMcpContracts(): void
    {
        $providers = [
            'ControleOnline\\Service\\McpLinkedInProviderInterface' => 'ControleOnline\\Service\\Mcp\\LinkedInMcpProvider',
            'ControleOnline\\Service\\McpMercadoLivreProviderInterface' => 'ControleOnline\\Service\\Mcp\\MercadoLivreMcpProvider',
        ];

        foreach ($providers as $contract => $provider) {
            self::assertTrue(interface_exists($contract), 'Missing installed MCP contract: ' . $contract);
            self::assertTrue(class_exists($provider), 'Integration provider must be loadable: ' . $provider);
            self::assertTrue(is_subclass_of($provider, $contract), 'Integration provider must implement its installed MCP contract.');
        }
    }
}
