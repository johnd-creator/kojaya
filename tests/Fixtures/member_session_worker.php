<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (app()->environment() !== 'testing' || config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'kojaya_test') {
    exit(90);
}
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$actual = \Illuminate\Support\Facades\DB::selectOne('show data_directory')->data_directory;
if (str_replace('\\', '/', $actual) !== str_replace('\\', '/', $input['cluster'])) {
    exit(91);
}
file_put_contents($input['ready'], 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($input['barrier'])) {
    if (microtime(true) > $deadline) {
        exit(92);
    } usleep(10000);
}
try {
    $service = app(\App\Services\Auth\MemberSessionService::class);
    if ($input['mode'] === 'revoke') {
        $service->revoke($input['id'], 'fixture_revoke');
    } else {
        $service->renew($input['credential']);
    }
    echo '200';
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    echo (string) $e->getStatusCode();
}
