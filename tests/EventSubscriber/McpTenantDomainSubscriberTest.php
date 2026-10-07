<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\McpTenantDomainSubscriber;
use ControleOnline\Service\DomainService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class McpTenantDomainSubscriberTest extends TestCase
{
    public function testTenantPathOverridesClientDomainHeadersBeforeAuthentication(): void
    {
        $request = Request::create('https://api.controleonline.com/mcp/app.controleonline.com');
        $request->attributes->set('_route', 'controleonline_mcp_tenant');
        $request->attributes->set('tenantDomain', 'app.controleonline.com');
        $request->headers->set('app-domain', 'other-company.controleonline.com');
        $request->headers->set('origin', 'https://other-company.controleonline.com');

        $domainService = $this->createMock(DomainService::class);
        $domainService->expects(self::never())->method('getMainDomain');
        (new McpTenantDomainSubscriber($domainService))->applyTenantDomain($this->requestEvent($request));

        self::assertSame('app.controleonline.com', $request->headers->get('app-domain'));
        self::assertSame('app.controleonline.com', $request->attributes->get('app-domain'));
    }

    public function testBareMcpPathUsesMainDomainInsteadOfOriginHeader(): void
    {
        $request = Request::create('https://api.controleonline.com/mcp');
        $request->attributes->set('_route', 'controleonline_mcp');
        $request->headers->set('app-domain', 'other-company.controleonline.com');
        $request->headers->set('origin', 'https://other-company.controleonline.com');

        $domainService = $this->createMock(DomainService::class);
        $domainService->expects(self::once())->method('getMainDomain')->willReturn('api.controleonline.com');
        (new McpTenantDomainSubscriber($domainService))->applyTenantDomain($this->requestEvent($request));

        self::assertSame('api.controleonline.com', $request->headers->get('app-domain'));
        self::assertSame('api.controleonline.com', $request->attributes->get('app-domain'));
    }

    public function testNonMainRequestsAreNotModified(): void
    {
        $request = Request::create('https://api.controleonline.com/mcp/app.controleonline.com');
        $request->attributes->set('_route', 'controleonline_mcp_tenant');
        $request->attributes->set('tenantDomain', 'app.controleonline.com');

        $domainService = $this->createMock(DomainService::class);
        $domainService->expects(self::never())->method('getMainDomain');
        (new McpTenantDomainSubscriber($domainService))->applyTenantDomain(
            $this->requestEvent($request, HttpKernelInterface::SUB_REQUEST),
        );

        self::assertNull($request->headers->get('app-domain'));
        self::assertNull($request->attributes->get('app-domain'));
    }

    private function requestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(KernelInterface::class), $request, $requestType);
    }
}
