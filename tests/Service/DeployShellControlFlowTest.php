<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class DeployShellControlFlowTest extends TestCase
{
    private function deployScript(): string
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        foreach ($workflow['jobs']['deploy']['steps'] as $step) {
            if (($step['name'] ?? '') === 'Deploy application over SSH') {
                self::assertFalse($step['with']['script_stop'] ?? false,
                    'Per-line SSH error instrumentation breaks valid false branches.');
                return $step['with']['script'];
            }
        }
        self::fail('SSH deploy step missing');
    }

    public function testWritableNonRootDeploymentContinuesPastPermissionsCheck(): void
    {
        $script = $this->deployScript();
        $start = strpos($script, 'if id www-data');
        $end = strpos($script, 'nohup php', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $checks = substr($script, $start, $end - $start);
        $sandbox = sys_get_temp_dir().'/deploy-shell-'.bin2hex(random_bytes(8));
        mkdir($sandbox);
        mkdir($sandbox.'/var');
        mkdir($sandbox.'/var/cache');
        mkdir($sandbox.'/var/log');
        try {
            // Model the staging SSH account without needing root or a www-data account.
            $setup = "set -e\nid() { case \"\$1\" in -u) echo 1000;; -un) echo staging;; www-data) return 0;; esac; }\n";
            exec('cd '.escapeshellarg($sandbox).' && bash -c '.escapeshellarg($setup.$checks.'echo DEPLOY_CONTINUES').' 2>&1', $output, $exit);
            self::assertSame(0, $exit, implode("\n", $output));
            self::assertContains('DEPLOY_CONTINUES', $output);
        } finally {
            rmdir($sandbox.'/var/cache');
            rmdir($sandbox.'/var/log');
            rmdir($sandbox.'/var');
            rmdir($sandbox);
        }
    }

    public function testRemoteScriptStillStopsOnCommandFailure(): void
    {
        $script = $this->deployScript();
        $firstLine = strtok($script, "\n");
        exec('bash -c '.escapeshellarg($firstLine."\nfalse\necho MUST_NOT_DEPLOY").' 2>&1', $output, $exit);
        self::assertNotSame(0, $exit);
        self::assertNotContains('MUST_NOT_DEPLOY', $output);
    }

    public function testCleanupPreservesRetiredComposerManagedModulePaths(): void
    {
        $script = $this->deployScript();

        self::assertStringContainsString(
            "git clean -ffd -e '/modules/controleonline/'",
            $script
        );
    }
}
