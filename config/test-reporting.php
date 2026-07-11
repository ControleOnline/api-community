<?php

declare(strict_types=1);

if (!function_exists('ensureTestReportDirectory')) {
    function ensureTestReportDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create PHPUnit report directory at "%s".', $directory));
        }
    }
}
