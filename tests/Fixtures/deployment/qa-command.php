<?php

$statePath = getenv('QA_REHEARSAL_DIRECTORY').'/state.json';
$state = json_decode(file_get_contents($statePath), true, flags: JSON_THROW_ON_ERROR);
$tool = $argv[1];
$args = array_slice($argv, 2);
$scenario = getenv('QA_REHEARSAL_SCENARIO');
$state['commands'][] = $tool.' '.implode(' ', $args);
$failure = false;

if ($tool === 'git') {
    $path = $args[1];
    $operation = $args[2] ?? '';
    $operationArgs = array_slice($args, 3);
    if ($operation === 'status' && $path === getenv('QA_REHEARSAL_CANDIDATE') && $scenario === 'dirty-candidate') {
        echo ' M candidate.php';
    } elseif ($operation === 'rev-parse') {
        echo $path === getenv('QA_REHEARSAL_CANDIDATE')
            ? ($scenario === 'mismatch-candidate' ? str_repeat('c', 40) : $state['target_sha'])
            : $state['serving_sha'];
    } elseif ($operation === 'checkout') {
        $sha = end($operationArgs);
        $state['commands'][] = 'serving-checkout '.$sha;
        $state['serving_sha'] = $sha;
    }
} elseif ($tool === 'php' && str_ends_with($args[0], '/bin/qa-runtime-permissions.php')) {
    $state['permission_passes'] = ($state['permission_passes'] ?? 0) + 1;
    $state['commands'][] = 'runtime-permissions '.$state['permission_passes'];
    $failure = $scenario === 'runtime-permissions'
        || ($scenario === 'post-optimize-permissions' && $state['permission_passes'] === 2);
} elseif ($tool === 'php' && $args[0] === 'artisan') {
    $command = $args[1];
    $isCandidate = getcwd() === getenv('QA_REHEARSAL_CANDIDATE');
    $state['commands'][] = ($isCandidate ? 'candidate-' : 'serving-').$command.' '.implode(' ', array_slice($args, 2));
    $failure = match (true) {
        $command === 'qa:deployment-identity' && $isCandidate && $scenario === 'candidate-identity' => true,
        $command === 'qa:deployment-identity' && ! $isCandidate && $scenario === 'post-identity' => true,
        $command === 'app:release-preflight' && $scenario === 'preflight' => true,
        $command === 'backup:database' && $scenario === 'backup' => true,
        $command === 'backup:verify' && $scenario === 'verify' => true,
        $command === 'down' && $scenario === 'maintenance' => true,
        $command === 'migrate' && $scenario === 'migration' => true,
        $command === 'optimize' && $scenario === 'postmigration' => true,
        default => false,
    };
    if ($command === 'backup:database' && ! $failure) {
        echo 'Backup ID:   kojaya-qa-kojaya_qa-20260930T000000Z-'.substr($state['target_sha'], 0, 7)."\n";
    }
    if ($command === 'migrate') {
        $state['migration'] = $failure ? 'partial' : 'completed';
    }
} elseif ($tool === 'systemctl') {
    $unit = end($args);
    $property = current(array_filter($args, static fn (string $argument): bool => str_starts_with($argument, '--property=')));
    if (str_starts_with((string) $property, '--property=LoadState')) {
        $missingQueue = $scenario === 'queue-missing' && $unit === 'kojaya-qa-queue.service';
        $missingScheduler = $scenario === 'scheduler-missing' && $unit === 'kojaya-qa-schedule.timer';
        echo $missingQueue || $missingScheduler ? 'not-found' : 'loaded';
    } elseif (str_starts_with((string) $property, '--property=ActiveState')) {
        $queueActive = $scenario === 'queue-active' && $unit === 'kojaya-qa-queue.service';
        $schedulerActive = $scenario === 'scheduler-active' && $unit === 'kojaya-qa-schedule.timer';
        echo $queueActive || $schedulerActive ? 'active' : 'inactive';
    } elseif (in_array('is-enabled', $args, true)) {
        echo $scenario === 'scheduler-enabled' && $unit === 'kojaya-qa-schedule.timer' ? 'enabled' : 'disabled';
    }
} elseif ($tool === 'npm' && $args[0] === 'ci' && $scenario === 'candidate-dependencies') {
    $failure = true;
}

file_put_contents($statePath, json_encode($state, JSON_THROW_ON_ERROR));
exit($failure ? 23 : 0);
