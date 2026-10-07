<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use ControleOnline\Service\DomainService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Applies the MCP URL tenant before firewall authentication and domain-aware services run. */
final class McpTenantDomainSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly DomainService $domainService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // RouterListener runs at priority 32 and the firewall at priority 8.
        return [KernelEvents::REQUEST => ['applyTenantDomain', 16]];
    }

    public function applyTenantDomain(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!in_array($route, ['controleonline_mcp', 'controleonline_mcp_tenant'], true)) {
            return;
        }

        $tenantDomain = $request->attributes->get('tenantDomain');
        $domain = is_string($tenantDomain) && trim($tenantDomain) !== ''
            ? trim($tenantDomain)
            : $this->domainService->getMainDomain();

        if ($domain === '') {
            return;
        }

        // DomainService checks this header before Origin/Referer and main-domain fallback.
        // Override client supplied values so the URL path is authoritative for MCP.
        $request->headers->set('app-domain', $domain);
        $request->attributes->set('app-domain', $domain);
    }
}
