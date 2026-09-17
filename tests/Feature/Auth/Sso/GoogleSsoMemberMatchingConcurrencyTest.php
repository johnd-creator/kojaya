<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Sso;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Real PostgreSQL Concurrency Test: Two separate PHP processes concurrently
 * invoke MemberGoogleSsoMatchingService::resolve() for the same canonical member
 * and same Google provider identity.
 *
 * Invariants tested:
 * - Exactly one User created.
 * - Exactly one SocialAccount created.
 * - Member linked to the single created User.
 * - Member status unchanged.
 * - Exactly one 'member.google_sso_linked' audit log emitted.
 * - Zero duplicate account corruption.
 *
 * Requires PostgreSQL (DB_CONNECTION=pgsql); skipped under SQLite.
 */
class GoogleSsoMemberMatchingConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    /** @var array<string, string> */
    private array $dbConfig = [];

    private static string $requiredConnection = '';

    public static function setUpBeforeClass(): void
    {
        self::$requiredConnection = getenv('DB_CONNECTION') ?: 'sqlite';
    }

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        if (self::$requiredConnection !== 'pgsql') {
            self::fail(
                'GoogleSsoMemberMatchingConcurrencyTest REQUIRES PostgreSQL. Got DB_CONNECTION='.self::$requiredConnection
                .'. Use: vendor/bin/phpunit --configuration phpunit.pgsql.xml tests/Feature/Auth/Sso/GoogleSsoMemberMatchingConcurrencyTest.php'
            );
        }

        $this->dbConfig = [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_DATABASE') ?: 'kojaya_test',
            'username' => getenv('DB_USERNAME') ?: 'kojaya',
            'password' => getenv('DB_PASSWORD') ?: 'kojaya',
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
        ];

        $this->refreshApplication();

        putenv('DB_CONNECTION=pgsql');
        putenv('DB_DATABASE='.$this->dbConfig['database']);
        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_DATABASE'] = $this->dbConfig['database'];
        $_SERVER['DB_CONNECTION'] = 'pgsql';
        $_SERVER['DB_DATABASE'] = $this->dbConfig['database'];

        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', $this->dbConfig);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-sso-concurrency-'.bin2hex(random_bytes(8));
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
                foreach (glob($resultDir.'/*') ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($resultDir);
            }

            @rmdir($this->workingDirectory);
        }

        parent::tearDown();
    }

    public function test_concurrent_first_time_google_sso_matches_atomically_without_duplicates(): void
    {
        $org = Organization::factory()->create();

        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'user_id' => null,
            'email' => 'concurrency.member@example.com',
            'nama_anggota' => 'Concurrency Member',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $workerFile = $this->workingDirectory.'/worker.php';
        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        file_put_contents($workerFile, $this->workerScript());

        $googleId = 'google-sub-concurrency-1001';
        $email = 'concurrency.member@example.com';

        // Launch two worker processes concurrently targeting the exact same member and googleId
        $processes = [
            $this->startWorker($workerFile, $startFile, $resultDir, 'worker_a', $googleId, $email),
            $this->startWorker($workerFile, $startFile, $resultDir, 'worker_b', $googleId, $email),
        ];

        // Give workers time to boot and wait on startFile
        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'worker_a'),
            $this->finishWorker($processes[1], $resultDir, 'worker_b'),
        ];

        $successes = array_filter($results, fn (array $r): bool => $r['ok'] === true);
        $failures = array_filter($results, fn (array $r): bool => $r['ok'] !== true);

        $this->assertCount(1, $successes, 'Expected exactly one worker to succeed linking the account.');
        $this->assertCount(1, $failures, 'Expected exactly one worker to encounter conflict/fail.');

        // Invariants
        // 1. CooperativeMember count unchanged
        $this->assertSame(1, CooperativeMember::query()->where('email', $email)->count());

        // 2. Exactly one User created
        $users = User::query()->where('email', $email)->get();
        $this->assertCount(1, $users);
        $user = $users->first();
        $this->assertSame('Concurrency Member', $user->name);
        $this->assertTrue($user->hasRole('Anggota'));

        // 3. Member user_id points to that User
        $member->refresh();
        $this->assertSame($user->id, $member->user_id);
        $this->assertSame('google', $member->sso_provider);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->validation_status);

        // 4. Exactly one SocialAccount exists for provider_id
        $socials = SocialAccount::query()
            ->where('provider', 'google')
            ->where('provider_id', $googleId)
            ->get();
        $this->assertCount(1, $socials);
        $this->assertSame($user->id, $socials->first()->user_id);

        // 5. Exactly one audit log emitted
        $audits = AuditLog::query()
            ->where('action', 'member.google_sso_linked')
            ->where('subject_id', $member->id)
            ->get();
        $this->assertCount(1, $audits);
    }

    private function workerScript(): string
    {
        $dbHost = $this->dbConfig['host'];
        $dbPort = $this->dbConfig['port'];
        $dbDatabase = $this->dbConfig['database'];
        $dbUsername = $this->dbConfig['username'];
        $dbPassword = $this->dbConfig['password'];

        return <<<PHP
<?php

declare(strict_types=1);

use App\Services\Auth\Sso\MemberGoogleSsoMatchingService;
use Illuminate\Contracts\Console\Kernel;
use Laravel\Socialite\Two\User as SocialiteUser;

[\$script, \$repoPath, \$startFile, \$resultDir, \$workerName, \$googleId, \$email] = \$argv;

while (! file_exists(\$startFile)) {
    usleep(10000);
}

putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('DB_CONNECTION=pgsql');
putenv("DB_HOST={$dbHost}");
putenv("DB_PORT={$dbPort}");
putenv("DB_DATABASE={$dbDatabase}");
putenv("DB_USERNAME={$dbUsername}");
putenv("DB_PASSWORD={$dbPassword}");

\$_ENV['APP_ENV'] = 'testing';
\$_ENV['CACHE_STORE'] = 'array';
\$_ENV['SESSION_DRIVER'] = 'array';
\$_ENV['QUEUE_CONNECTION'] = 'sync';
\$_ENV['DB_CONNECTION'] = 'pgsql';

require \$repoPath.'/vendor/autoload.php';
\$app = require \$repoPath.'/bootstrap/app.php';
\$app->make(Kernel::class)->bootstrap();

config()->set('database.default', 'pgsql');
config()->set('database.connections.pgsql.host', '{$dbHost}');
config()->set('database.connections.pgsql.port', '{$dbPort}');
config()->set('database.connections.pgsql.database', '{$dbDatabase}');
config()->set('database.connections.pgsql.username', '{$dbUsername}');
config()->set('database.connections.pgsql.password', '{$dbPassword}');

\$socialiteUser = new SocialiteUser();
\$socialiteUser->id = \$googleId;
\$socialiteUser->name = 'Google Worker Name';
\$socialiteUser->email = \$email;
\$socialiteUser->user = [
    'sub' => \$googleId,
    'email' => \$email,
    'email_verified' => true,
];

\$matchingService = \$app->make(MemberGoogleSsoMatchingService::class);

try {
    \$result = \$matchingService->resolve(\$socialiteUser);
    \$payload = [
        'worker' => \$workerName,
        'ok' => \$result->success,
        'code' => \$result->resultCode,
        'reason' => \$result->reason,
        'user_id' => \$result->user?->id,
    ];
} catch (\Throwable \$e) {
    \$payload = [
        'worker' => \$workerName,
        'ok' => false,
        'class' => get_class(\$e),
        'message' => \$e->getMessage(),
    ];
}

file_put_contents(\$resultDir.'/'.\$workerName.'.json', json_encode(\$payload, JSON_THROW_ON_ERROR));
PHP;
    }

    /**
     * @return resource
     */
    private function startWorker(
        string $workerFile,
        string $startFile,
        string $resultDir,
        string $workerName,
        string $googleId,
        string $email
    ) {
        $repoPath = base_path();
        $command = sprintf(
            'exec php %s %s %s %s %s %s %s',
            escapeshellarg($workerFile),
            escapeshellarg($repoPath),
            escapeshellarg($startFile),
            escapeshellarg($resultDir),
            escapeshellarg($workerName),
            escapeshellarg($googleId),
            escapeshellarg($email)
        );

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            $this->fail("Failed to spawn worker {$workerName}");
        }

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    /**
     * @param  array{process: mixed, pipes: array<int, resource>}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker, string $resultDir, string $workerName): array
    {
        $stderr = '';
        if (is_resource($worker['pipes'][2] ?? null)) {
            $stderr = (string) stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1] ?? null)) {
            fclose($worker['pipes'][1]);
        }

        $exitCode = 0;
        if (is_resource($worker['process'] ?? null)) {
            $exitCode = proc_close($worker['process']);
        }

        $resultFile = $resultDir.'/'.$workerName.'.json';
        $contents = file_exists($resultFile) ? file_get_contents($resultFile) : '';

        if ($contents === false || $contents === '') {
            return [
                'ok' => false,
                'worker' => $workerName,
                'class' => 'WorkerCrashed',
                'message' => "Worker [{$workerName}] did not write a result file. Exit code: {$exitCode}. Stderr: ".trim($stderr),
            ];
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! array_key_exists('ok', $decoded)) {
            return [
                'ok' => false,
                'worker' => $workerName,
                'class' => 'MalformedResult',
                'message' => "Worker [{$workerName}] wrote a malformed result. Contents: {$contents}",
            ];
        }

        return $decoded;
    }
}
