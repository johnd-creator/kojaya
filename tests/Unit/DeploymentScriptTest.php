<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class DeploymentScriptTest extends TestCase
{
    private string $directory;

    private string $bash;

    private const TARGET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const PREVIOUS = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = str_replace('\\', '/', sys_get_temp_dir()).'/kojaya-deploy-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/bin', 0700, true);
        $this->bash = PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Git/bin/bash.exe' : '/bin/bash';
        $this->assertFileExists($this->bash);
        $source = dirname(__DIR__, 2);
        file_put_contents($this->directory.'/deploy.sh', str_replace("\r\n", "\n", file_get_contents($source.'/bin/deploy.sh')));
        copy($source.'/tests/Fixtures/deployment/command.php', $this->directory.'/command.php');
        foreach (['git', 'php', 'composer', 'npm'] as $tool) {
            $extensions = PHP_OS_FAMILY === 'Windows' ? ' -n -d extension_dir="$REHEARSAL_EXTENSION_DIR" -d extension=php_pdo_sqlite.dll' : '';
            file_put_contents($this->directory.'/bin/'.$tool, "#!/usr/bin/env bash\nexec \"\$REHEARSAL_PHP\"".$extensions.' "$REHEARSAL_DIRECTORY/command.php" '.$tool.' "$@"'."\n");
            chmod($this->directory.'/bin/'.$tool, 0700);
        }
        file_put_contents($this->directory.'/state.json', json_encode([
            'code' => self::PREVIOUS, 'maintenance' => false, 'migration' => 'untouched', 'commands' => [],
        ], JSON_THROW_ON_ERROR));
        $db = new PDO('sqlite:'.$this->directory.'/source.sqlite');
        $db->exec('CREATE TABLE business_fixture (id INTEGER PRIMARY KEY)');
        $db->exec('INSERT INTO business_fixture VALUES (1)');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    #[DataProvider('invalidTargets')]
    public function test_invalid_target_never_reaches_backup_or_mutation(string $target): void
    {
        $result = $this->deploy('success', $target);
        $this->assertSame(2, $result->getExitCode(), $result->getErrorOutput());
        $this->assertSame([], $this->state()['commands']);
        $this->assertUnchangedDatabase();
    }

    public static function invalidTargets(): array
    {
        return [['main'], ['HEAD'], ['latest'], ['v1.0.0-rc.6'], ['abcdef0'], [''], [str_repeat('a', 39)]];
    }

    #[DataProvider('earlyFailures')]
    public function test_early_failure_preserves_previous_online_release(string $scenario): void
    {
        $result = $this->deploy($scenario);
        $this->assertNotSame(0, $result->getExitCode());
        $state = $this->state();
        $this->assertSame(self::PREVIOUS, $state['code']);
        $this->assertFalse($state['maintenance']);
        $this->assertNotContains('php artisan down --retry=60', $state['commands']);
        $this->assertUnchangedDatabase();
    }

    public static function earlyFailures(): array
    {
        return [['dirty'], ['status'], ['fetch'], ['mismatch'], ['backup']];
    }

    #[DataProvider('preMigrationFailures')]
    public function test_failure_before_migration_keeps_maintenance_and_database_unchanged(string $scenario): void
    {
        $result = $this->deploy($scenario);
        $this->assertNotSame(0, $result->getExitCode());
        $state = $this->state();
        $this->assertTrue($state['maintenance']);
        $this->assertNotContains('php artisan migrate --force', $state['commands']);
        $this->assertNotContains('php artisan up', $state['commands']);
        $this->assertStringContainsString('migration=not-started', $result->getErrorOutput());
        $this->assertSame(in_array($scenario, ['maintenance', 'checkout']) ? self::PREVIOUS : self::TARGET, $state['code']);
        $this->assertUnchangedDatabase();
    }

    public static function preMigrationFailures(): array
    {
        return [['maintenance'], ['checkout'], ['composer'], ['cache-clear'], ['preflight'], ['npm-install'], ['build']];
    }

    public function test_success_orders_backup_preflight_migration_and_up(): void
    {
        $result = $this->deploy('success');
        $this->assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        $state = $this->state();
        $commands = $state['commands'];
        $backup = array_search('php artisan backup:database --purpose=pre-deploy', $commands, true);
        $down = array_search('php artisan down --retry=60', $commands, true);
        $preflight = array_search('php artisan app:release-preflight --strict-production --require-android-push', $commands, true);
        $migration = array_search('php artisan migrate --force', $commands, true);
        foreach ([$backup, $down, $preflight, $migration] as $position) {
            $this->assertIsInt($position);
        }
        $this->assertLessThan($down, $backup);
        $this->assertLessThan($migration, $preflight);
        $this->assertSame(['php artisan optimize', 'php artisan queue:restart', 'php artisan up'], array_slice($commands, -3));
        $this->assertFalse($state['maintenance']);
        $this->assertSame('complete', $state['migration']);
        $this->assertSame(self::PREVIOUS, $state['backup_code']);
        $this->assertStringContainsString('Operator smoke acceptance is still required', $result->getOutput());
    }

    public function test_workflow_validates_sha_before_checkout_and_rejects_identity_mismatch(): void
    {
        $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
        $steps = $workflow['jobs']['deploy']['steps'];
        $this->assertSame('Validate approved SHA', $steps[0]['name']);
        $this->assertSame('actions/checkout@v4', $steps[1]['uses']);
        file_put_contents($this->directory.'/validate.sh', $steps[0]['run']);
        foreach (['main', 'HEAD', 'v1.0.0-rc.6', 'abcdef0', self::TARGET] as $ref) {
            $process = new Process([$this->bash, $this->directory.'/validate.sh'], $this->directory, ['REQUESTED_REF' => $ref]);
            $process->run();
            $this->assertSame($ref === self::TARGET ? 0 : 1, $process->getExitCode());
        }
        file_put_contents($this->directory.'/resolve.sh', $steps[2]['run']);
        $process = $this->process('export PATH="$REHEARSAL_BIN:/usr/bin:/bin"; export REQUESTED_REF="$REHEARSAL_TARGET"; export GITHUB_OUTPUT="$REHEARSAL_DIRECTORY/output"; bash "$REHEARSAL_DIRECTORY/resolve.sh"', 'success');
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertFileDoesNotExist($this->directory.'/output');
    }

    private function deploy(string $scenario, string $target = self::TARGET): Process
    {
        $process = $this->process('export PATH="$REHEARSAL_BIN:/usr/bin:/bin"; bash "$REHEARSAL_DIRECTORY/deploy.sh" --ref "$REHEARSAL_TARGET"', $scenario, $target);
        $process->run();

        return $process;
    }

    private function process(string $command, string $scenario, string $target = self::TARGET): Process
    {
        $bin = $this->directory.'/bin';
        if (PHP_OS_FAMILY === 'Windows') {
            $bin = '/'.strtolower($bin[0]).substr($bin, 2);
        }

        return new Process([$this->bash, '--noprofile', '--norc', '-c', $command], $this->directory, [
            'REHEARSAL_DIRECTORY' => $this->directory, 'REHEARSAL_BIN' => $bin,
            'REHEARSAL_PHP' => str_replace('\\', '/', PHP_BINARY),
            'REHEARSAL_EXTENSION_DIR' => str_replace('\\', '/', ini_get('extension_dir')),
            'REHEARSAL_SCENARIO' => $scenario, 'REHEARSAL_TARGET' => $target,
        ], timeout: 60);
    }

    private function state(): array
    {
        return json_decode(file_get_contents($this->directory.'/state.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    private function assertUnchangedDatabase(): void
    {
        $db = new PDO('sqlite:'.$this->directory.'/source.sqlite');
        $this->assertSame(1, (int) $db->query('SELECT COUNT(*) FROM business_fixture')->fetchColumn());
        $this->assertSame(0, (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'schema_v2'")->fetchColumn());
        $this->assertSame('untouched', $this->state()['migration']);
    }
}
