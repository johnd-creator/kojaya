<?php

declare(strict_types=1);

use App\Models\PosCashierShift;
use App\Models\User;
use App\Services\Cooperative\PosCashierShiftService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$rawInput = fgets(STDIN);
if ($rawInput === false) {
    throw new RuntimeException('Worker received no input.');
}

$input = json_decode($rawInput, true, flags: JSON_THROW_ON_ERROR);
if ($input['connection']['driver'] !== 'pgsql' || $input['connection']['database'] !== 'kojaya_test') {
    throw new RuntimeException('Worker is restricted to the isolated PostgreSQL test database.');
}

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'pgsql',
    'database.connections.pgsql' => $input['connection'],
    'cache.default' => 'array',
    'session.driver' => 'array',
    'queue.default' => 'sync',
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

if (trim((string) fgets(STDIN)) !== 'GO') {
    throw new RuntimeException('Missing parent barrier signal.');
}

try {
    if ($input['action'] === 'close') {
        $shift = PosCashierShift::query()->findOrFail($input['shift_id']);
        $closed = app(PosCashierShiftService::class)->closeShift($shift, (float) $input['closing_cash']);
        $result = ['outcome' => 'closed', 'id' => $closed->id];
    } elseif ($input['action'] === 'sale') {
        $sale = app(PosTransactionService::class)->create($input['sale'], $actor);
        $result = ['outcome' => 'created', 'id' => $sale->id];
    } else {
        throw new InvalidArgumentException("Unknown action: {$input['action']}");
    }
} catch (ValidationException $exception) {
    $result = ['outcome' => 'validation_error', 'errors' => $exception->errors()];
} catch (Throwable $exception) {
    $result = ['outcome' => 'error', 'class' => $exception::class, 'message' => $exception->getMessage()];
}

echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
