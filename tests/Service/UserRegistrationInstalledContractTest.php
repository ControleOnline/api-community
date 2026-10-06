<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ControleOnline\Entity\{People, User, Email, Timezone, Language};
use ControleOnline\Service\{UserService, FileService, PeopleRoleService, DomainService, PdfService, PeopleService};
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationInstalledContractTest extends TestCase
{
    public function testFreshRegistrationUsesRealEntitiesAndServicesWithoutTransientEntityQueries(): void
    {
        self::assertNull((new People())->getId());
        $manager = $this->createMock(EntityManagerInterface::class);
        $timezone = (new Timezone())->setName('America/Sao_Paulo');
        $language = (new Language())->setLanguage('pt-BR');
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::exactly(4))->method('findOneBy')->willReturn(null, null, $timezone, $language);
        $manager->expects(self::exactly(4))->method('getRepository')->willReturnCallback(
            static function (string $class) use ($repo): EntityRepository {
                self::assertContains($class, [User::class, Email::class, Timezone::class, Language::class]);
                return $repo;
            }
        );
        $persisted = [];
        $manager->expects(self::exactly(3))->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persisted): void { $persisted[] = $entity; }
        );
        $manager->expects(self::once())->method('flush');
        $manager->expects(self::never())->method('createQueryBuilder');
        $domain = $this->createStub(DomainService::class);
        $security = $this->createStub(TokenStorageInterface::class);
        $roles = new PeopleRoleService($manager, $security, $domain);
        $files = new FileService($manager, $domain, $this->createStub(PdfService::class), $this->createStub(PeopleService::class), new RequestStack());
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->willReturn('hashed-secret');
        $service = new UserService($manager, $hasher, $files, $security, $roles, new RequestStack());
        $session = $service->createAccountSessionFromContent(json_encode([
            'name' => 'Maria Silva', 'email' => 'maria@example.com', 'password' => 'secret',
        ], JSON_THROW_ON_ERROR));
        self::assertSame([], $session['roles']);
        self::assertSame('', $session['avatar']);
        self::assertSame(0, $session['active']);
        self::assertSame('maria@example.com', $session['email']);
        self::assertCount(3, $persisted);
    }
}
