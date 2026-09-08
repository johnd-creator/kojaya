<?php

declare(strict_types=1);

use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
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
    $service = app(PosTransactionService::class);
    if ($input['action'] === 'approve') {
        $voidRequest = PosVoidRequest::query()->findOrFail($input['void_request_id']);
        $service->approveVoid($voidRequest, $actor);
        $result = ['outcome' => 'approved'];
    } elseif ($input['action'] === 'reject') {
        $voidRequest = PosVoidRequest::query()->findOrFail($input['void_request_id']);
        $service->rejectVoid($voidRequest, $actor, 'Rejected by worker');
        $result = ['outcome' => 'rejected'];
    } elseif ($input['action'] === 'request_void') {
        $tx = PosTransaction::query()->findOrFail($input['transaction_id']);
        $req = $service->requestVoid($tx, $actor, 'Void requested by worker');
        $result = ['outcome' => 'requested', 'id' => $req->id];
    } else {
        throw new InvalidArgumentException("Unknown action: {$input['action']}");
    }
} catch (ValidationException $exception) {
    $result = ['outcome' => 'validation_error', 'errors' => $exception->errors()];
} catch (\Throwable $exception) {
    $result = ['outcome' => 'error', 'class' => $exception::class, 'message' => $exception->getMessage()];
}

echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
