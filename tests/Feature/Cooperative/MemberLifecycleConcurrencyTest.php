<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * True concurrency test: two separate PHP processes race approveFinal vs reject
 * against the same member using PostgreSQL row-level locking.
 *
 * Requires PostgreSQL and runs only when DB_CONNECTION=pgsql.
 * In CI this runs in the postgres-concurrency job.
 */
class MemberLifecycleConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('MemberLifecycleConcurrencyTest requires PostgreSQL (DB_CONNECTION=pgsql).');
        }

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-lifecycle-concurrency-'.bin2hex(random_bytes(8));
        mkdir($this->workingDirectory, 0777, true);

        Artisan::call('migrate:fresh', [
            '--database' => 'pgsql',
            '--force' => true,
        ]);

        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        if ($this->workingDirectory !== '') {
            foreach (glob($this->workingDirectory.'/*') ?: [] as $file) {
                @unlink($file);
            }

            $resultDir = $this->workingDirectory.'/results';
            if (is_dir($resultDir)) {
                @rmdir($resultDir);
            }

            @rmdir($this->workingDirectory);
        }

        parent::tearDown();
    }

    public function test_concurrent_approve_final_vs_reject_exactly_one_wins(): void
    {
        $org = Organization::factory()->create();

        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $workerFile = $this->workingDirectory.'/worker.php';
        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        file_put_contents($workerFile, $this->workerScript());

        $processes = [
            $this->startWorker($workerFile, $startFile, $resultDir, 'approve', $pengurus->id, $member->id),
            $this->startWorker($workerFile, $startFile, $resultDir, 'reject', $admin->id, $member->id),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'approve'),
            $this->finishWorker($processes[1], $resultDir, 'reject'),
        ];

        $successes = array_filter($results, fn (array $r): bool => $r['ok']);
        $failures = array_filter($results, fn (array $r): bool => ! $r['ok']);

        $this->assertCount(1, $successes, 'Exactly one transition should succeed.');
        $this->assertCount(1, $failures, 'Exactly one transition should fail due to row lock.');

        $member->refresh();
        $winner = reset($successes);

        if ($winner['action'] === 'approve') {
            $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->validation_status);
            $this->assertSame('ACTIVE', $member->status);
        } else {
            $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->validation_status);
            $this->assertSame('INACTIVE', $member->status);
        }

        $transitionAudits = AuditLog::query()
            ->where('action', 'member.status.transitioned')
            ->where('subject_id', $member->id)
            ->count();

        $this->assertSame(1, $transitionAudits, 'Exactly one terminal audit should exist.');
    }

    private function workerScript(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Cooperative\MemberStatusTransitionService;
use Illuminate\Contracts\Console\Kernel;

[$script, $repoPath, $startFile, $resultDir, $action, $actorId, $memberId] = $argv;

while (! file_exists($startFile)) {
    usleep(10000);
}

putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');

$_ENV['APP_ENV'] = 'testing';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';

require $repoPath.'/vendor/autoload.php';

$app = require $repoPath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$actor = User::query()->findOrFail((int) $actorId);
$member = CooperativeMember::query()->findOrFail((int) $memberId);
$service = app(MemberStatusTransitionService::class);

$resultFile = $resultDir.'/'.$action.'.json';

try {
    if ($action === 'approve') {
        $service->approveFinal($member, $actor, 'concurrent approve');
    } else {
        $service->reject($member, $actor, 'concurrent reject');
    }

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'action' => $action,
        'validation_status' => $member->refresh()->validation_status,
        'status' => $member->status,
    ], JSON_THROW_ON_ERROR));

    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'action' => $action,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ], JSON_THROW_ON_ERROR));

    exit(1);
}
PHP;
    }

    /**
     * @return array{process: mixed, pipes: array<int, resource>}
     */
    private function startWorker(string $workerFile, string $startFile, string $resultDir, string $action, int $actorId, string $memberId): array
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $startFile,
                $resultDir,
                $action,
                (string) $actorId,
                $memberId,
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path(),
        );

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    /**
     * @param  array{process: mixed, pipes: array<int, resource>}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker, string $resultDir, string $action): array
    {
        $stderr = '';

        if (is_resource($worker['pipes'][2])) {
            $stderr = stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1])) {
            fclose($worker['pipes'][1]);
        }

        if (is_resource($worker['process'])) {
            proc_close($worker['process']);
        }

        $resultFile = $resultDir.'/'.$action.'.json';
        $contents = file_exists($resultFile) ? file_get_contents($resultFile) : '{}';

        return json_decode($contents ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }
}
