<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class QaDeploymentScriptTest extends TestCase
{
    private string $directory;

    private string $candidate;

    private string $serving;

    private string $bash;

    private const TARGET = '0b02ad2441c1e4e8ca5f41933e33597246c07b9b';

    private const PREVIOUS = '878b3678d4d29bec635d918ebbd98d9367878b2a';

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/kojaya-qa-deploy-test-'.bin2hex(random_bytes(8));
        $this->candidate = $this->directory.'/candidate';
        $this->serving = $this->directory.'/serving';
        mkdir($this->candidate, 0700, true);
        mkdir($this->serving, 0700, true);
        mkdir($this->directory.'/bin', 0700, true);
        mkdir($this->directory.'/tmp', 0700, true);
        mkdir($this->serving.'/storage/app/private', 0700, true);
        $this->bash = '/bin/bash';
        $source = dirname(__DIR__, 2);
        copy($source.'/bin/deploy-qa.sh', $this->directory.'/deploy-qa.sh');
        copy($source.'/tests/Fixtures/deployment/qa-command.php', $this->directory.'/qa-command.php');
        foreach (['git', 'php', 'composer', 'npm', 'systemctl'] as $tool) {
            file_put_contents($this->directory.'/bin/'.$tool, "#!/usr/bin/env bash\nexec \"\$QA_REHEARSAL_PHP\" \"\$QA_REHEARSAL_DIRECTORY/qa-command.php\" {$tool} \"\$@\"\n");
            chmod($this->directory.'/bin/'.$tool, 0700);
        }
        file_put_contents($this->directory.'/runtime.env', "APP_ENV=qa\nDB_DATABASE=kojaya_qa\n");
        chmod($this->directory.'/runtime.env', 0600);
        file_put_contents($this->directory.'/traffic-hold', implode("\n", [
            'environment=qa', 'status=held', 'approved_sha='.self::TARGET, 'created_at_epoch='.time(), '',
        ]));
        chmod($this->directory.'/traffic-hold', 0600);
        file_put_contents($this->serving.'/.env', "APP_ENV=development\nDB_DATABASE=kojaya\n");
        chmod($this->serving.'/.env', 0600);
        file_put_contents($this->directory.'/state.json', json_encode([
            'candidate_sha' => self::TARGET,
            'serving_sha' => self::PREVIOUS,
            'target_sha' => self::TARGET,
            'migration' => 'not-started',
            'commands' => [],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        foreach (glob(sys_get_temp_dir().'/kojaya-qa-recovery.*') ?: [] as $recoveryDirectory) {
            (new Filesystem)->deleteDirectory($recoveryDirectory);
        }
        parent::tearDown();
    }

    public function test_non_exact_or_unapproved_ref_fails_before_commands(): void
    {
        $result = $this->deploy('success', 'main');
        $this->assertSame(2, $result->getExitCode());
        $this->assertSame([], $this->state()['commands']);
        $this->assertSame(self::PREVIOUS, $this->state()['serving_sha']);
        $this->assertSame('not-started', $this->state()['migration']);
    }

    #[DataProvider('preMutationFailures')]
    public function test_pre_mutation_failures_never_change_serving_code_or_runtime(string $scenario): void
    {
        $result = $this->deploy($scenario);
        $this->assertNotSame(0, $result->getExitCode(), $result->getErrorOutput());
        $state = $this->state();
        $this->assertSame(self::PREVIOUS, $state['serving_sha']);
        $this->assertStringContainsString('DB_DATABASE=kojaya', file_get_contents($this->serving.'/.env'));
        $this->assertSame('not-started', $state['migration']);
        $this->assertStringNotContainsString('serving-checkout ', implode("\n", $state['commands']));
        $this->assertNotContains('serving-migrate --force --no-interaction', $state['commands']);
    }

    public static function preMutationFailures(): array
    {
        return [
            ['dirty-candidate'], ['mismatch-candidate'], ['candidate-identity'], ['candidate-dependencies'],
            ['preflight'], ['backup'], ['verify'], ['traffic'], ['queue'], ['scheduler'],
        ];
    }

    public function test_identity_command_rejects_legacy_database_before_network_connection(): void
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'qa:deployment-identity',
            '--expect=kojaya_qa',
            '--no-interaction',
        ], dirname(__DIR__, 2), [
            'APP_ENV' => 'qa',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'kojaya',
            'DB_USERNAME' => 'not-used',
            'DB_PASSWORD' => 'not-used',
        ], timeout: 10);
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('QA database identity: FAIL', $process->getOutput());
        $this->assertStringNotContainsString('could not connect', strtolower($process->getErrorOutput()));
    }

    public function test_dirty_candidate_fails_closed(): void
    {
        $state = $this->state();
        $state['scenario'] = 'dirty-candidate';
        file_put_contents($this->directory.'/state.json', json_encode($state, JSON_THROW_ON_ERROR));
        $result = $this->deploy('dirty-candidate');
        $this->assertNotSame(0, $result->getExitCode());
        $this->assertSame(self::PREVIOUS, $this->state()['serving_sha']);
        $this->assertSame('not-started', $this->state()['migration']);
    }

    public function test_pre_migration_post_cutover_identity_failure_restores_code_and_runtime(): void
    {
        $result = $this->deploy('post-identity');
        $this->assertNotSame(0, $result->getExitCode());
        $state = $this->state();
        $this->assertSame(self::PREVIOUS, $state['serving_sha']);
        $this->assertStringContainsString('DB_DATABASE=kojaya', file_get_contents($this->serving.'/.env'));
        $this->assertSame('not-started', $state['migration']);
        $this->assertSame(2, count(array_filter($state['commands'], fn (string $command): bool => str_starts_with($command, 'serving-checkout '))));
    }

    public function test_migration_failure_keeps_target_and_never_rolls_back_database_or_code(): void
    {
        $result = $this->deploy('migration');
        $this->assertNotSame(0, $result->getExitCode());
        $state = $this->state();
        $this->assertSame(self::TARGET, $state['serving_sha']);
        $this->assertStringContainsString('DB_DATABASE=kojaya_qa', file_get_contents($this->serving.'/.env'));
        $this->assertSame('partial', $state['migration']);
        $this->assertSame(1, count(array_filter($state['commands'], fn (string $command): bool => str_starts_with($command, 'serving-checkout '))));
        $this->assertStringContainsString('No automatic database recovery was attempted', $result->getErrorOutput());
    }

    public function test_success_orders_all_qa_gates_and_never_starts_workers(): void
    {
        $result = $this->deploy('success');
        $this->assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        $commands = $this->state()['commands'];
        $positions = [];
        foreach ([
            'candidate-qa:deployment-identity',
            'candidate-app:release-preflight',
            'candidate-backup:database',
            'candidate-backup:verify',
            'php artisan down',
            'serving-checkout '.self::TARGET,
            'serving-qa:deployment-identity',
            'serving-migrate --force --no-interaction',
            'serving-up ',
        ] as $expected) {
            $positions[] = $this->positionStartingWith($commands, $expected);
            $this->assertIsInt(end($positions), 'Missing command order marker: '.$expected);
        }
        for ($index = 1; $index < count($positions); $index++) {
            $this->assertLessThan($positions[$index], $positions[$index - 1]);
        }
        $this->assertSame('completed', $this->state()['migration']);
        $this->assertStringContainsString('DB_DATABASE=kojaya_qa', file_get_contents($this->serving.'/.env'));
        $this->assertStringNotContainsString('queue:restart', implode("\n", $commands));
        $this->assertStringNotContainsString('systemctl start', implode("\n", $commands));
        $this->assertNotFalse($this->positionStartingWith($commands, 'candidate-backup:verify backups/database/kojaya-qa-kojaya_qa-'));
        $this->assertStringContainsString('--disk=local --directory=backups/database --expected-database=kojaya_qa --require-private-permissions', implode("\n", $commands));
        $this->assertStringContainsString('--strict-release-candidate --require-android-push --no-interaction', implode("\n", $commands));
        $productionScript = file_get_contents(dirname(__DIR__, 2).'/bin/deploy.sh');
        $this->assertStringContainsString('--strict-production', $productionScript);
        $this->assertStringNotContainsString('--strict-release-candidate', $productionScript);
    }

    private function deploy(string $scenario, string $target = self::TARGET): Process
    {
        if ($scenario === 'traffic') {
            file_put_contents($this->directory.'/traffic-hold', 'environment=qa\nstatus=released\napproved_sha='.self::TARGET.'\ncreated_at_epoch='.time().'\n');
            chmod($this->directory.'/traffic-hold', 0600);
        }
        $bin = $this->directory.'/bin';
        $command = 'export PATH="$QA_REHEARSAL_BIN:/usr/bin:/bin"; export TMPDIR="$QA_REHEARSAL_DIRECTORY/tmp"; bash "$QA_REHEARSAL_DIRECTORY/deploy-qa.sh"'
            .' --ref "$QA_REHEARSAL_TARGET" --approved-sha "$QA_REHEARSAL_APPROVED"'
            .' --candidate-dir "$QA_REHEARSAL_CANDIDATE" --serving-dir "$QA_REHEARSAL_SERVING"'
            .' --runtime-env "$QA_REHEARSAL_DIRECTORY/runtime.env" --traffic-hold-attestation "$QA_REHEARSAL_DIRECTORY/traffic-hold"';

        $process = new Process([$this->bash, '--noprofile', '--norc', '-c', $command], $this->directory, [
            'QA_REHEARSAL_DIRECTORY' => $this->directory,
            'QA_REHEARSAL_BIN' => $bin,
            'QA_REHEARSAL_CANDIDATE' => $this->candidate,
            'QA_REHEARSAL_SERVING' => $this->serving,
            'QA_REHEARSAL_PHP' => PHP_BINARY,
            'QA_REHEARSAL_SCENARIO' => $scenario,
            'QA_REHEARSAL_TARGET' => $target,
            'QA_REHEARSAL_APPROVED' => self::TARGET,
            'TMPDIR' => $this->directory.'/tmp',
        ], timeout: 60);
        $process->run();

        return $process;
    }

    private function positionStartingWith(array $commands, string $prefix): int|false
    {
        foreach ($commands as $position => $command) {
            if (str_starts_with($command, $prefix)) {
                return $position;
            }
        }

        return false;
    }

    private function state(): array
    {
        return json_decode(file_get_contents($this->directory.'/state.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
