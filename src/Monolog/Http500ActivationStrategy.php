<?php

namespace App\Monolog;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\LogRecord;

class Http500ActivationStrategy implements ActivationStrategyInterface
{
    public function __construct(private string $level = 'error') {}

    public function isHandlerActivated(LogRecord $record): bool
    {
        $exception = $record->context['exception'] ?? null;

        if (!$exception instanceof \Throwable) {
            return false;
        }


        return true;
    }
}
