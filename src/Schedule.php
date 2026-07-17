<?php

namespace App;

use ControleOnline\Service\CronJobService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    private ?SymfonySchedule $schedule = null;

    public function __construct(
        #[Autowire(service: 'cache.scheduler')]
        private CacheInterface $schedulerCache,
        private LockFactory $lockFactory,
        private CronJobService $cronJobService,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        if ($this->schedule instanceof SymfonySchedule) {
            return $this->schedule;
        }

        $schedule = (new SymfonySchedule())
            ->lock($this->lockFactory->createLock('scheduler:cron-jobs'))
            ->stateful($this->schedulerCache)
            ->processOnlyLastMissedRun(true);

        foreach ($this->cronJobService->getConfiguredJobs() as $job) {
            if (
                !($job['enabled'] ?? false)
                || !($job['isValid'] ?? false)
                || trim((string) ($job['key'] ?? '')) === ''
                || trim((string) ($job['command'] ?? '')) === ''
            ) {
                continue;
            }

            $schedule->add(
                RecurringMessage::cron(
                    (string) $job['cronExpression'],
                    new RunCommandMessage(
                        sprintf('app:cron:run-job %s', (string) $job['key'])
                    )
                )
            );
        }

        return $this->schedule = $schedule;
    }
}
