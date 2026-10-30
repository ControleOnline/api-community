<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\McpLinkedInProvider;
use ControleOnline\Entity\People;
use ControleOnline\Service\LinkedInService;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class McpLinkedInProviderTest extends TestCase
{
    public function testCreatePostDeniedWithoutCompanyAccess(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $li = $this->createMock(LinkedInService::class);
        $scope = $this->createMock(McpCompanyScopeProviderInterface::class);
        $scope->method('listForCurrentUser')->willReturn([['id' => 2, 'name' => 'Other']]);

        $provider = new McpLinkedInProvider($em, $li, $scope);
        $this->expectException(\RuntimeException::class);
        $provider->createPost(1, 'hello');
    }

    public function testCreatePostDelegatesWhenAllowed(): void
    {
        $people = $this->createMock(People::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($people);
        $li = $this->createMock(LinkedInService::class);
        $li->expects(self::once())->method('createMemberPost')->with($people, 'hello world')
            ->willReturn(['success' => true, 'post_id' => 'urn:li:share:1']);
        $scope = $this->createMock(McpCompanyScopeProviderInterface::class);
        $scope->method('listForCurrentUser')->willReturn([['id' => 1, 'name' => 'Co']]);

        $provider = new McpLinkedInProvider($em, $li, $scope);
        $result = $provider->createPost(1, 'hello world');
        self::assertTrue($result['success']);
        self::assertSame('urn:li:share:1', $result['post_id']);
    }
}
