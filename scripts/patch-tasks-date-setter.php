<?php

declare(strict_types=1);

// Temporary compatibility patch for the published tasks v1.0.7 archive.
// Remove after a corrected tasks release is available on Packagist.
$path = dirname(__DIR__) . '/vendor/controleonline/tasks/src/Entity/Task.php';
$beforeHash = 'cbed796231f89c4662abe6cf1c88e2b661b5e99afcaf9643c7518c7c27691b2e';
$afterHash = '30a9ecf1d02b00c035b59d5e59cbc4032f33c75e12308a1d60b3be4272acefce';

try {
    $source = file_get_contents($path);
    if ($source === false) {
        throw new RuntimeException('The installed tasks entity could not be read.');
    }

    $hash = hash('sha256', $source);
    if ($hash === $afterHash) {
        fwrite(STDOUT, "Tasks date setter compatibility patch already applied.\n");
        exit(0);
    }
    if ($hash !== $beforeHash) {
        throw new RuntimeException('The tasks archive changed; review this compatibility patch before installation.');
    }

    // Keep the original public setter and all AI fields; remove only its duplicate.
    $duplicate = implode("\r\n", [
        '    public function setAlterDate(\\DateTimeInterface $alterDate): self',
        '    {',
        '        $this->alterDate = $alterDate;',
        '        return $this;',
        '    }',
        '',
        '',
    ]);
    if (substr_count($source, $duplicate) !== 1) {
        throw new RuntimeException('The duplicate date setter does not match the reviewed patch.');
    }
    $patched = str_replace($duplicate, '', $source);
    if (hash('sha256', $patched) !== $afterHash) {
        throw new RuntimeException('The patched entity does not match the reviewed result.');
    }
    if (file_put_contents($path, $patched, LOCK_EX) !== strlen($patched)) {
        throw new RuntimeException('The tasks compatibility patch could not be written.');
    }
    fwrite(STDOUT, "Applied tasks v1.0.7 duplicate date setter compatibility patch.\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
