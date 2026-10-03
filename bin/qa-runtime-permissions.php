<?php

require __DIR__.'/../app/Support/DeploymentRuntimePermissions.php';

try {
    if ($argc !== 3 || ! ctype_digit($argv[2])) {
        throw new RuntimeException('Checkout and verified runtime group are required.');
    }
    (new App\Support\DeploymentRuntimePermissions)->apply($argv[1], (int) $argv[2]);
    echo "QA runtime filesystem permissions: PASS\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "QA runtime filesystem permissions: FAIL\n");
    exit(1);
}
