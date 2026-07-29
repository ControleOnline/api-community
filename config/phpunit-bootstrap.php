<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';
require_once __DIR__.'/test-reporting.php';

ensureTestReportDirectory(dirname(__DIR__).'/var/tests/phpunit/backend');
