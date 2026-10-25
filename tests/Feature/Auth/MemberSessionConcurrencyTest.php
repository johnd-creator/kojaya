<?php

namespace Tests\Feature\Auth;

use App\Enums\TokenApp;
use App\Models\CooperativeMember;
use App\Models\MemberAuthSession;
use App\Models\MemberSessionRenewal;
use App\Models\User;
use App\Services\Auth\MemberSessionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Separate processes, PostgreSQL row locks, synthetic accounts only. */
class MemberSessionConcurrencyTest extends TestCase
{
    private string $cluster;

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('kojaya_test', DB::connection()->getDatabaseName());
        $this->cluster = DB::selectOne('show data_directory')->data_directory;
        $expected = getenv('SESSION_TEST_CLUSTER');
        if ($expected) {
            $this->assertSame(str_replace('\\', '/', $expected), str_replace('\\', '/', $this->cluster));
        }
        Artisan::call('migrate', ['--force' => true]);
        $this->seed(RolePermissionSeeder::class);
        config(['member_sessions.enabled' => true]);
    }

    private function bundle(): array
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        return app(MemberSessionService::class)->issue($user, TokenApp::MEMBER, 'DEMO concurrency', null, true);
    }

    private function race(array $bundle, array $modes): array
    {
        $dir = sys_get_temp_dir().'/kojaya-session-race-'.bin2hex(random_bytes(8));
        mkdir($dir);
        $processes = [];
        $env = [];
        foreach ($_SERVER as $k => $v) {
            if (is_string($v)) {
                $env[$k] = $v;
            }
        }
        $env['DB_PASSWORD'] = (string) config('database.connections.pgsql.password');
        foreach ($modes as $i => $mode) {
            $p = new Process([PHP_BINARY, base_path('tests/Fixtures/member_session_worker.php')], base_path(), $env, null, 30);
            $p->setInput(json_encode(['cluster' => $this->cluster, 'ready' => $dir.'/'.$i, 'barrier' => $dir.'/go', 'mode' => $mode, 'id' => $bundle['session']['id'], 'credential' => $bundle['session']['renewal_token']], JSON_THROW_ON_ERROR));
            $p->start();
            $processes[] = $p;
        }
        $deadline = microtime(true) + 15;
        while (count(glob($dir.'/*')) < count($modes)) {
            if (microtime(true) > $deadline) {
                foreach ($processes as $p) {
                    $p->stop();
                } $this->fail('Worker did not reach barrier');
            }
            usleep(10000);
        }
        file_put_contents($dir.'/go', 'go');
        $results = [];
        foreach ($processes as $p) {
            $p->wait();
            $this->assertSame(0, $p->getExitCode(), $p->getErrorOutput());
            $results[] = trim($p->getOutput());
        }

        return $results;
    }

    public function test_simultaneous_use_has_one_winner_then_committed_family_reuse_revocation(): void
    {
        $b = $this->bundle();
        $results = $this->race($b, ['renew', 'renew']);
        sort($results);
        $this->assertSame(['200', '401'], $results);
        $this->assertNotNull(MemberAuthSession::findOrFail($b['session']['id'])->revoked_at);
        $this->assertSame(0, PersonalAccessToken::where('member_session_id', $b['session']['id'])->count());
        $this->assertSame(2, MemberSessionRenewal::where('member_auth_session_id', $b['session']['id'])->count());
    }

    public function test_rotation_and_revoke_cannot_leave_a_live_successor(): void
    {
        $b = $this->bundle();
        $results = $this->race($b, ['renew', 'revoke']);
        $this->assertContains('200', $results);
        $this->assertNotNull(MemberAuthSession::findOrFail($b['session']['id'])->revoked_at);
        $this->assertSame(0, PersonalAccessToken::where('member_session_id', $b['session']['id'])->count());
    }
}
