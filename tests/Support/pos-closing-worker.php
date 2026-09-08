<?php

use App\Models\User;
use App\Services\Cooperative\PosDailyClosingService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$input = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
if ($input['connection']['driver'] !== 'pgsql' || $input['connection']['database'] !== 'kojaya_test') {
    throw new RuntimeException('Worker is restricted to the isolated PostgreSQL test database.');
}

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'pgsql', 'database.connections.pgsql' => $input['connection'],
    'cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync',
]);
DB::purge('pgsql');
if (DB::selectOne('select current_database() as name')->name !== 'kojaya_test') {
    throw new RuntimeException('Unexpected effective worker database.');
}
DB::statement("SET lock_timeout = '12s'");
DB::statement("SET statement_timeout = '15s'");
$actor = User::query()->findOrFail($input['actor_id']);
echo json_encode(['outcome' => 'ready', 'pid' => (int) DB::selectOne('select pg_backend_pid() as pid')->pid])."\n";
flush();
if (trim(fgets(STDIN)) !== 'GO') {
    throw new RuntimeException('Missing parent barrier signal.');
}

try {
    if ($input['action'] === 'close') {
        $closing = app(PosDailyClosingService::class)->closeDay($input['date'], $actor, $input['organization_id']);
        $result = ['outcome' => 'closed', 'id' => $closing->id];
    } else {
        $sale = app(PosTransactionService::class)->create($input['sale'], $actor);
        $result = ['outcome' => 'sold', 'id' => $sale->id];
    }
} catch (ValidationException $exception) {
    $result = ['outcome' => 'rejected', 'errors' => $exception->errors()];
} catch (Throwable $exception) {
    $result = ['outcome' => 'error', 'class' => $exception::class, 'message' => $exception->getMessage()];
}
echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
