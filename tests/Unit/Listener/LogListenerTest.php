<?php

namespace App\Tests\Unit\Listener;

use ControleOnline\Listener\LogListener;
use ControleOnline\Service\SystemLogWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use PHPUnit\Framework\TestCase;

class LogListenerTest extends TestCase
{
    public function testLogsInsertedEntitiesAfterIdGeneration(): void
    {
        $listener = $this->createListener(7);
        $entity = new LogListenerTestEntity(null, 'Pedido', new \DateTimeImmutable('2026-04-18 12:30:00'));
        $metadata = $this->createMetadata();

        $writer = $this->createMock(SystemLogWriter::class);
        $writer
            ->expects(self::once())
            ->method('write')
            ->with(
                'entity',
                'insert',
                LogListenerTestEntity::class,
                15,
                self::callback(function (array $object): bool {
                    self::assertSame(15, $object['id']);
                    self::assertSame('Pedido', $object['name']);
                    self::assertSame('2026-04-18 12:30:00', $object['createdAt']);

                    return true;
                })
            )
            ->willReturn(true);

        $em = $this->createEntityManager(
            $metadata,
            [$entity]
        );

        $listener = new LogListener($writer);
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

        $writer = $this->createMock(SystemLogWriter::class);
        $writer
            ->expects(self::once())
            ->method('write')
            ->with(
                'entity',
                'update',
                LogListenerTestEntity::class,
                25,
                self::callback(function (array $object): bool {
                    self::assertSame(['Pedido antigo', 'Pedido atualizado'], $object['name']);
                    self::assertSame(
                        ['2026-04-18 13:00:00', '2026-04-18 13:15:00'],
                        $object['createdAt']
                    );

                    return true;
                })
            )
            ->willReturn(true);

        $em = $this->createEntityManager(
            $metadata,
            [],
            [$entity],
            [],
            $changeSets
        );

        $listener = new LogListener($writer);
        $listener->onFlush(new OnFlushEventArgs($em));
        $listener->postFlush(new PostFlushEventArgs($em));
    }

    private function createListener(int $userId): LogListener
    {
        $writer = $this->createMock(SystemLogWriter::class);

        return new LogListener($writer);
    }

    private function createEntityManager(
        ClassMetadata $metadata,
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
