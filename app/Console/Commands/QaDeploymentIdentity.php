<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class QaDeploymentIdentity extends Command
{
    protected $signature = 'qa:deployment-identity {--expect=kojaya_qa}';

    protected $description = 'Fail closed unless Laravel and an independent PostgreSQL connection identify the QA database';

    public function handle(): int
    {
        $expected = (string) $this->option('expect');
        $connectionName = (string) config('database.default');
        $configuredConnection = config("database.connections.{$connectionName}");

        if ($expected !== 'kojaya_qa' || app()->environment() !== 'qa' || ! is_array($configuredConnection) || ($configuredConnection['driver'] ?? null) !== 'pgsql') {
            return $this->failed();
        }

        try {
            $laravelConnection = DB::connection($connectionName);
            $connection = $laravelConnection->getConfig();
            if (($connection['driver'] ?? null) !== 'pgsql' || ($connection['database'] ?? null) !== $expected) {
                return $this->failed();
            }

            $laravelDatabase = (string) ($laravelConnection->selectOne('SELECT current_database() AS database_name')->database_name ?? '');
            $directDatabase = $this->probeIndependentConnection($connection);

            if ($laravelDatabase !== $expected || $directDatabase !== $expected) {
                return $this->failed();
            }
        } catch (Throwable) {
            return $this->failed();
        }

        $this->line('QA database identity: PASS');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $connection */
    private function probeIndependentConnection(array $connection): string
    {
        $host = (string) ($connection['unix_socket'] ?? $connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '5432');
        $database = (string) ($connection['database'] ?? '');
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        if (isset($connection['sslmode']) && $connection['sslmode'] !== '') {
            $dsn .= ';sslmode='.(string) $connection['sslmode'];
        }

        $options = $connection['options'] ?? [];
        $pdo = new PDO(
            $dsn,
            (string) ($connection['username'] ?? ''),
            (string) ($connection['password'] ?? ''),
            is_array($options) ? $options : [],
        );

        return (string) $pdo->query('SELECT current_database()')->fetchColumn();
    }

    private function failed(): int
    {
        $this->error('QA database identity: FAIL');

        return self::FAILURE;
    }
}
