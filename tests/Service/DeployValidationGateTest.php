<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class DeployValidationGateTest extends TestCase
{
    public function testDeploymentRequiresReusableBackendValidation(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        self::assertArrayHasKey('validate', $workflow['jobs']);
        $validation = $workflow['jobs']['validate'];
        self::assertSame('./.github/workflows/pull-request-checks.yml', $validation['uses']);
        self::assertStringContainsString('github.sha', $validation['with']['ref']);
        self::assertStringContainsString('needs.configure.outputs.branch', $validation['with']['ref']);
        self::assertContains('validate', $workflow['jobs']['deploy']['needs']);
        self::assertStringContainsString("needs.validate.result == 'success'", $workflow['jobs']['deploy']['if']);
        self::assertStringContainsString("needs.configure.result == 'success'", $workflow['jobs']['deploy']['if']);
        self::assertArrayNotHasKey('tests', $workflow['jobs'], 'Backend checks must finish before the server is changed.');
    }

    public function testMaintenanceCanRunWithoutInstallingDependencies(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $condition = $workflow['jobs']['deploy']['if'] ?? '';
        self::assertStringContainsString("needs.validate.result == 'skipped'", $condition);
        foreach (['cache-recovery', 'inspect-prod-errors', 'inspect-staging-appearance'] as $operation) {
            self::assertStringContainsString("needs.configure.outputs.operation == '$operation'", $condition);
        }
        self::assertStringContainsString("needs.configure.outputs.operation == 'deploy-staging-appearance'", $workflow['jobs']['validate']['if']);
    }

    public function testReusableChecksExposeTheRevisionTheyActuallyTest(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/pull-request-checks.yml');
        self::assertArrayHasKey('workflow_call', $workflow['on']);
        self::assertSame('${{ jobs.baseline-checks.outputs.sha }}', $workflow['on']['workflow_call']['outputs']['sha']['value']);
        $job = $workflow['jobs']['baseline-checks'];
        self::assertSame('${{ steps.revision.outputs.sha }}', $job['outputs']['sha']);
        $steps = array_column($job['steps'], null, 'name');
        self::assertStringContainsString('git rev-parse HEAD', $steps['Resolve checked revision']['run']);
        self::assertStringContainsString('composer install', $steps['Install reviewed backend packages']['run']);
        self::assertStringContainsString('composer test:backend', $steps['Verify complete backend suite']['run']);
    }

    public function testRemoteCheckoutUsesValidatedShaInsteadOfTheMovingBranch(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $steps = array_column($workflow['jobs']['deploy']['steps'], null, 'name');
        $ssh = $steps['Deploy application over SSH'];
        self::assertSame('${{ needs.validate.outputs.sha }}', $ssh['env']['DEPLOY_SHA']);
        self::assertSame('${{ needs.validate.outputs.sha || github.sha }}', $ssh['env']['APPEARANCE_SOURCE_SHA']);
        self::assertStringContainsString('git reset --hard "${DEPLOY_SHA}"', $ssh['with']['script']);
        self::assertStringNotContainsString('git reset --hard "origin/${BRANCH}"', $ssh['with']['script']);
    }

    public function testOnlySuccessfulValidationAllowsPackageDeployment(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        foreach (['deploy', 'deploy-staging-appearance'] as $operation) {
            foreach (['success', 'failure', 'cancelled', 'skipped'] as $validation) {
                self::assertSame($validation === 'success', $this->allowsDeploy($workflow, $operation, $validation));
            }
        }
        foreach (['cache-recovery', 'inspect-prod-errors', 'inspect-staging-appearance'] as $operation) {
            self::assertTrue($this->allowsDeploy($workflow, $operation, 'skipped'));
            self::assertFalse($this->allowsDeploy($workflow, $operation, 'failure'));
        }
        self::assertFalse($this->allowsDeploy($workflow, 'unknown-operation', 'skipped'));
        self::assertFalse($this->allowsDeploy($workflow, 'deploy', 'success', 'failure'));
        self::assertFalse($this->allowsDeploy($workflow, 'deploy', 'success', 'success', true));
    }

    private function allowsDeploy(array $workflow, string $operation, string $validation, string $configure = 'success', bool $cancelled = false): bool
    {
        // These GitHub boolean operators have the same meaning in PHP.
        $condition = strtr($workflow['jobs']['deploy']['if'], [
            'always()' => 'true',
            'cancelled()' => $cancelled ? 'true' : 'false',
            'needs.configure.result' => var_export($configure, true),
            'needs.validate.result' => var_export($validation, true),
            'needs.configure.outputs.operation' => var_export($operation, true),
        ]);
        return (bool) eval('return '.$condition.';');
    }

    public function testAdvancingRemoteBranchStillDeploysTheTestedCommit(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $steps = array_column($workflow['jobs']['deploy']['steps'], null, 'name');
        $script = $steps['Deploy application over SSH']['with']['script'];
        $start = strpos($script, 'if ! [[ "${DEPLOY_SHA}"');
        $lastCommand = 'git reset --hard "${DEPLOY_SHA}"';
        $end = strpos($script, $lastCommand, $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $checkout = substr($script, $start, $end + strlen($lastCommand) - $start);
        $sandbox = sys_get_temp_dir().'/deploy-revision-'.bin2hex(random_bytes(8));
        mkdir($sandbox);
        try {
            $setup = <<<'BASH'
set -eu
git init --quiet --initial-branch=master source
git -C source -c user.name=Test -c user.email=test@example.invalid commit --quiet --allow-empty -m reviewed
DEPLOY_SHA=$(git -C source rev-parse HEAD)
git clone --quiet source server
git -C source -c user.name=Test -c user.email=test@example.invalid commit --quiet --allow-empty -m unreviewed
BRANCH=master
cd server
BASH;
            $assertion = '\ntest "$(git rev-parse HEAD)" = "$DEPLOY_SHA"\ntest "$(git rev-parse origin/master)" != "$DEPLOY_SHA"';
            $assertion = str_replace('\\n', "\n", $assertion);
            exec('cd '.escapeshellarg($sandbox).' && bash -c '.escapeshellarg($setup."\n".$checkout.$assertion).' 2>&1', $output, $exit);
            self::assertSame(0, $exit, implode("\n", $output));
            exec('bash -c '.escapeshellarg("set -e\nDEPLOY_SHA=invalid\n".$checkout."\necho MUST_NOT_DEPLOY").' 2>&1', $invalidOutput, $invalidExit);
            self::assertNotSame(0, $invalidExit);
            self::assertNotContains('MUST_NOT_DEPLOY', $invalidOutput);
        } finally {
            exec('rm -rf -- '.escapeshellarg($sandbox));
        }
    }

    public function testDeploymentUsesTheLockProducedBySuccessfulChecks(): void
    {
        $checks = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/pull-request-checks.yml');
        self::assertArrayHasKey('lock_sha256', $checks['on']['workflow_call']['outputs']);
        $steps = array_column($checks['jobs']['baseline-checks']['steps'], null, 'name');
        self::assertSame('actions/upload-artifact@v4', $steps['Upload validated dependency lock']['uses']);
        self::assertSame('composer.lock', $steps['Upload validated dependency lock']['with']['path']);
        $deploy = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $steps = array_column($deploy['jobs']['deploy']['steps'], null, 'name');
        self::assertSame('actions/download-artifact@v4', $steps['Download validated dependency lock']['uses']);
        self::assertSame('appleboy/scp-action@v1', $steps['Stage validated dependency lock']['uses']);
        $ssh = $steps['Deploy application over SSH'];
        self::assertSame('${{ needs.validate.outputs.lock_sha256 }}', $ssh['env']['VALIDATED_LOCK_SHA256']);
        self::assertStringContainsString('sha256sum --check --status', $ssh['with']['script']);
        self::assertStringContainsString('cp "$VALIDATED_LOCK_PATH" composer.lock', $ssh['with']['script']);
        self::assertStringNotContainsString('run_composer update', $ssh['with']['script']);
    }

    public function testChangedLockStopsBeforeTheServerCheckout(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $steps = array_column($workflow['jobs']['deploy']['steps'], null, 'name');
        $script = $steps['Deploy application over SSH']['with']['script'];
        $start = strpos($script, 'if ! [[ "$VALIDATED_LOCK_SHA256"');
        $end = strpos($script, '# Never reset the server', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        self::assertLessThan(strpos($script, 'git reset --hard'), $end);
        $check = substr($script, $start, $end - $start);
        $lock = tempnam(sys_get_temp_dir(), 'validated-lock-');
        file_put_contents($lock, '{}');
        try {
            $prefix = "set -e\nVALIDATED_LOCK_PATH=".escapeshellarg($lock)."\nVALIDATED_LOCK_SHA256=".hash_file('sha256', $lock)."\n";
            exec('bash -c '.escapeshellarg($prefix.$check.'echo LOCK_ACCEPTED').' 2>&1', $validOutput, $validExit);
            self::assertSame(0, $validExit, implode("\n", $validOutput));
            file_put_contents($lock, '{"tampered":true}');
            exec('bash -c '.escapeshellarg($prefix.$check.'echo MUST_NOT_DEPLOY').' 2>&1', $invalidOutput, $invalidExit);
            self::assertNotSame(0, $invalidExit);
            self::assertNotContains('MUST_NOT_DEPLOY', $invalidOutput);
        } finally {
            unlink($lock);
        }
    }
}
