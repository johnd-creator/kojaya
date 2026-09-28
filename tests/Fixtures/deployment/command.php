<?php

// Only the disposable command-contract rehearsal invokes this fixture.
$directory = getenv('REHEARSAL_DIRECTORY');
$path = $directory.'/state.json';
$state = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
$tool = $argv[1];
$args = array_slice($argv, 2);
$state['commands'][] = $tool.' '.implode(' ', $args);
$scenario = getenv('REHEARSAL_SCENARIO');
$stage = '';
if ($tool === 'git') {
    $stage = $args[0];
    if ($stage === 'status' && $scenario === 'dirty') {
        echo ' M example.php';
    } elseif ($stage === 'rev-parse') {
        echo in_array(end($args), ['HEAD', 'HEAD^{commit}']) ? $state['code'] : ($scenario === 'mismatch' ? str_repeat('c', 40) : getenv('REHEARSAL_TARGET'));
    } elseif ($stage === 'checkout' && $scenario !== $stage) {
        $state['code'] = end($args);
    }
} elseif ($tool === 'composer') {
    $stage = 'composer';
} elseif ($tool === 'npm') {
    $stage = $args[0] === 'ci' ? 'npm-install' : 'build';
} elseif ($tool === 'php' && $args[0] === 'artisan') {
    $stage = match ($args[1]) {
        'backup:database' => 'backup', 'down' => 'maintenance',
        'optimize:clear' => 'cache-clear', 'app:release-preflight' => 'preflight',
        'migrate' => 'migration', 'optimize' => 'optimize',
        'queue:restart' => 'queue-restart', 'up' => 'application-up',
        default => throw new RuntimeException('Unexpected fixture command'),
    };
    if ($stage === 'backup' && $scenario !== 'backup') {
        copy($directory.'/source.sqlite', $directory.'/verified.sqlite');
        $state['backup_sha256'] = hash_file('sha256', $directory.'/verified.sqlite');
        $state['backup_code'] = $state['code'];
    }
    if ($stage === 'maintenance') {
        $state['maintenance'] = true;
    }
    if ($stage === 'migration') {
        $db = new PDO('sqlite:'.$directory.'/source.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE schema_v2 (id INTEGER PRIMARY KEY)');
        $state['migration'] = 'complete';
        if ($scenario === 'migration') {
            try {
                $db->exec('INSERT INTO missing_table VALUES (1)');
            } catch (PDOException) {
                $state['migration'] = 'partial';
            }
        }
    }
    if ($stage === 'application-up' && $scenario !== $stage) {
        $state['maintenance'] = false;
    }
}
file_put_contents($path, json_encode($state, JSON_THROW_ON_ERROR));
exit($scenario === $stage ? 23 : 0);
