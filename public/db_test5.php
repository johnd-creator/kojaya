<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $types = \App\Models\LeaveType::all();
    echo 'Leave Types Count: '.$types->count()."\n";
    echo $types->toJson(JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage();
}
exit;
