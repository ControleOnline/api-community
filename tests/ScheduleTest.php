<?php

namespace App\Tests;

use App\Schedule;
use ControleOnline\Service\CronJobService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Contracts\Cache\CacheInterface;

class ScheduleTest extends TestCase
{
    public function testBuildsRunCommandMessagesForConfiguredJobs(): void
    {
        $cronJobService = $this->createMock(CronJobService::class);
        $cronJobService->method('getConfiguredJobs')->willReturn([
            'maintenance_run' => [
                'key' => 'maintenance_run',
                'enabled' => true,
                'isValid' => true,
                'cronExpression' => '* * * * *',
                'command' => 'app:maintenance:run',
            ],
        ]);

        $schedule = new Schedule(
            $this->createMock(CacheInterface::class),
            new LockFactory(new InMemoryStore()),
            $cronJobService
        );

        $recurringMessages = $schedule->getSchedule()->getRecurringMessages();

        self::assertCount(1, $recurringMessages);

        $message = $recurringMessages[0];
        $messages = iterator_to_array(
            $message->getProvider()->getMessages(
                new MessageContext(
                    'default',
                    $message->getId(),
                    $message->getTrigger(),
                    new \DateTimeImmutable('2026-07-17 12:00:00')
                )
            )
        );

        self::assertCount(1, $messages);
        self::assertInstanceOf(RunCommandMessage::class, $messages[0]);
        self::assertSame('app:cron:run-job maintenance_run', $messages[0]->input);
    }
}
