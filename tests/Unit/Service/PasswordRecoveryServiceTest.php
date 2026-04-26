<?php

namespace App\Tests\Unit\Service;

use App\Service\EmailService;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PasswordRecovery;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PasswordRecoveryService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordRecoveryServiceTest extends TestCase
{
    public function testRequestsRecoveryUsingAssociatedEmailEvenWhenUsernameDiffers(): void
    {
        [$user, $secondaryEmail] = $this->createUserWithEmails(
            'login@example.com',
            ['primary@example.com', 'contact@example.com']
        );

        $userRepository = $this->createMock(ObjectRepository::class);
        $userRepository
            ->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria): ?User {
                if ($criteria === ['username' => 'contact@example.com']) {
                    return null;
                }

                self::fail('Unexpected user lookup: ' . json_encode($criteria));
            });
        $userRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['people' => $user->getPeople()])
            ->willReturn([$user]);

        $emailRepository = $this->createMock(ObjectRepository::class);
        $emailRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'contact@example.com'])
            ->willReturn($secondaryEmail);

        $manager = $this->createManager($userRepository, $emailRepository);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $emailService = $this->createMock(EmailService::class);
        $emailService
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                'contact@example.com',
                'Recuperacao de senha',
                self::callback($this->assertRecoveryEmailBody(...))
            );

        $service = new PasswordRecoveryService(
            $manager,
            $emailService,
            $this->createMock(UserService::class),
            $this->createDomainService(),
            $this->createValidator()
        );

        $service->requestRecoveryFromContent(json_encode([
            'username' => 'contact@example.com',
        ]));

        self::assertNotNull($user->getOauthHash());
        self::assertNotNull($user->getLostPassword());
    }

    public function testRequestsRecoveryUsingAnyEmailAttachedToTheUser(): void
    {
        [$user] = $this->createUserWithEmails(
            'login@example.com',
            ['primary@example.com', 'alternate@example.com']
        );

        $payload = new PasswordRecovery();
        $payload->username = 'login@example.com';
        $payload->email = 'alternate@example.com';

        $userRepository = $this->createMock(ObjectRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => 'login@example.com'])
            ->willReturn($user);

        $emailRepository = $this->createMock(ObjectRepository::class);
        $emailRepository->expects(self::never())->method('findOneBy');

        $manager = $this->createManager($userRepository, $emailRepository);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $emailService = $this->createMock(EmailService::class);
        $emailService
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                'alternate@example.com',
                'Recuperacao de senha',
                self::callback($this->assertRecoveryEmailBody(...))
            );

        $service = new PasswordRecoveryService(
            $manager,
            $emailService,
            $this->createMock(UserService::class),
            $this->createDomainService(),
            $this->createValidator()
        );

        $service->requestRecovery($payload);

        self::assertNotNull($user->getOauthHash());
        self::assertNotNull($user->getLostPassword());
    }

    private function createManager(
        ObjectRepository $userRepository,
        ObjectRepository $emailRepository
    ): EntityManagerInterface {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(
                function (string $className) use ($userRepository, $emailRepository): ObjectRepository {
                    return match ($className) {
                        User::class => $userRepository,
                        Email::class => $emailRepository,
                        default => throw new \RuntimeException('Unexpected repository: ' . $className),
                    };
                }
            );

        return $manager;
    }

    private function createDomainService(): DomainService
    {
        $domainService = $this->createMock(DomainService::class);
        $domainService
            ->method('getDomain')
            ->willReturn('https://app.example.com');

        return $domainService;
    }

    private function createValidator(): ValidatorInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        return $validator;
    }

    private function createUserWithEmails(string $username, array $emails): array
    {
        $people = new People();
        $user = new User();
        $user->setUsername($username);
        $user->setPeople($people);

        $emailEntities = [];
        foreach ($emails as $value) {
            $email = new Email();
            $email->setEmail($value);
            $email->setPeople($people);
            $people->getEmail()->add($email);
            $emailEntities[] = $email;
        }

        return [$user, ...$emailEntities];
    }

    private function assertRecoveryEmailBody(string $body): bool
    {
        self::assertStringContainsString('reset-password?hash=', $body);
        self::assertStringContainsString('&lost=', $body);

        return true;
    }
}
