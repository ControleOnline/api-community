<?php

namespace App\Tests\Unit\Listener;

use ControleOnline\Listener\LogListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogListenerTest extends TestCase
{
    public function testLogsInsertedEntitiesAfterIdGeneration(): void
    {
        $listener = $this->createListener(7);
        $entity = new LogListenerTestEntity(null, 'Pedido', new \DateTimeImmutable('2026-04-18 12:30:00'));
        $metadata = $this->createMetadata();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with('log', self::callback(function (array $data): bool {
                self::assertSame('entity', $data['type']);
                self::assertSame('insert', $data['action']);
                self::assertSame(LogListenerTestEntity::class, $data['class']);
                self::assertSame(15, $data['row']);
                self::assertSame(7, $data['user_id']);

                $object = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(15, $object['id']);
                self::assertSame('Pedido', $object['name']);
                self::assertSame('2026-04-18 12:30:00', $object['createdAt']);

                return true;
            }))
            ->willReturn(1);

        $em = $this->createEntityManager(
            $metadata,
            $connection,
            [$entity]
        );

        $listener->onFlush(new OnFlushEventArgs($em));

        $entity->id = 15;

        $listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testLogsUpdatedEntityChangeSets(): void
    {
        $listener = $this->createListener(12);
        $entity = new LogListenerTestEntity(25, 'Pedido atualizado', new \DateTimeImmutable('2026-04-18 13:15:00'));
        $metadata = $this->createMetadata();

        $changeSets = [
            spl_object_id($entity) => [
                'name' => ['Pedido antigo', 'Pedido atualizado'],
                'createdAt' => [
                    new \DateTimeImmutable('2026-04-18 13:00:00'),
                    new \DateTimeImmutable('2026-04-18 13:15:00'),
                ],
            ],
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with('log', self::callback(function (array $data): bool {
                self::assertSame('entity', $data['type']);
                self::assertSame('update', $data['action']);
                self::assertSame(LogListenerTestEntity::class, $data['class']);
                self::assertSame(25, $data['row']);
                self::assertSame(12, $data['user_id']);

                $object = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(['Pedido antigo', 'Pedido atualizado'], $object['name']);
                self::assertSame(
                    ['2026-04-18 13:00:00', '2026-04-18 13:15:00'],
                    $object['createdAt']
                );

                return true;
            }))
            ->willReturn(1);

        $em = $this->createEntityManager(
            $metadata,
            $connection,
            [],
            [$entity],
            [],
            $changeSets
        );

        $listener->onFlush(new OnFlushEventArgs($em));
        $listener->postFlush(new PostFlushEventArgs($em));
    }

    private function createListener(int $userId): LogListener
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new LogListenerTestUser($userId));

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return new LogListener($tokenStorage);
    }

    private function createEntityManager(
        ClassMetadata $metadata,
        Connection $connection,
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $changeSets = [],
        array $originalData = []
    ): EntityManagerInterface {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn($insertions);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn($updates);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn($deletions);
        $unitOfWork->method('getEntityChangeSet')->willReturnCallback(
            fn(object $entity): array => $changeSets[spl_object_id($entity)] ?? []
        );
        $unitOfWork->method('getOriginalEntityData')->willReturnCallback(
            fn(object $entity): array => $originalData[spl_object_id($entity)] ?? []
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($unitOfWork);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturnCallback(
            fn(string $className): ClassMetadata => $this->assertMetadataClass($metadata, $className)
        );

        return $em;
    }

    private function assertMetadataClass(ClassMetadata $metadata, string $className): ClassMetadata
    {
        self::assertSame(LogListenerTestEntity::class, $className);

        return $metadata;
    }

    private function createMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(LogListenerTestEntity::class);
        $metadata->mapField([
            'fieldName' => 'id',
            'type' => 'integer',
            'id' => true,
        ]);
        $metadata->mapField([
            'fieldName' => 'name',
            'type' => 'string',
        ]);
        $metadata->mapField([
            'fieldName' => 'createdAt',
            'type' => 'datetime_immutable',
            'nullable' => true,
        ]);
        $metadata->wakeupReflection(new RuntimeReflectionService());

        return $metadata;
    }
}

class LogListenerTestEntity
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?\DateTimeImmutable $createdAt = null
    ) {}
}

class LogListenerTestUser implements UserInterface
{
    public function __construct(private int $id) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return 'listener-test-user';
    }
}
