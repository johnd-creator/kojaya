<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    // Also run php artisan migrate programmatically to apply the correct migration we wrote
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

    // Run the seeder
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'LeaveTypeSeeder', '--force' => true]);
    $seedOutput = \Illuminate\Support\Facades\Artisan::output();

    echo "Migrate Output:\n$migrateOutput\n";
    echo "Seed Output:\n$seedOutput\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n".$e->getTraceAsString();
}
exit;
