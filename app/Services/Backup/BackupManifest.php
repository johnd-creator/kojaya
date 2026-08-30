<?php

namespace App\Services\Backup;

use JsonSerializable;

class BackupManifest implements JsonSerializable
{
    /**
     * @param  array<string, int>  $rowCounts
     * @param  array<string, mixed>  $offsiteCopy
     */
    public function __construct(
        public readonly string $backupId,
        public readonly string $createdAt,
        public readonly string $applicationEnvironment,
        public readonly string $applicationGitSha,
        public readonly string $databaseEngine,
        public readonly string $databaseName,
        public readonly ?string $databaseHost,
        public readonly string|int|null $databasePort,
        public readonly ?string $databaseServerVersion,
        public readonly string $backupFilename,
        public readonly string $backupFormat,
        public readonly int $backupSizeBytes,
        public readonly string $sha256,
        public readonly string $purpose,
        public readonly string $verificationStatus,
        public readonly ?string $verifiedAt,
        public readonly array $rowCounts = [],
        public readonly array $offsiteCopy = [],
        public readonly int $schemaVersion = 1,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            backupId: (string) ($data['backup_id'] ?? ''),
            createdAt: (string) ($data['created_at'] ?? ''),
            applicationEnvironment: (string) ($data['application_environment'] ?? ''),
            applicationGitSha: (string) ($data['application_git_sha'] ?? ''),
            databaseEngine: (string) ($data['database_engine'] ?? ''),
            databaseName: (string) ($data['database_name'] ?? ''),
            databaseHost: isset($data['database_host']) ? (string) $data['database_host'] : null,
            databasePort: $data['database_port'] ?? null,
            databaseServerVersion: isset($data['database_server_version']) ? (string) $data['database_server_version'] : null,
            backupFilename: (string) ($data['backup_filename'] ?? ''),
            backupFormat: (string) ($data['backup_format'] ?? ''),
            backupSizeBytes: (int) ($data['backup_size_bytes'] ?? 0),
            sha256: (string) ($data['sha256'] ?? ''),
            purpose: (string) ($data['purpose'] ?? 'manual'),
            verificationStatus: (string) ($data['verification_status'] ?? 'unverified'),
            verifiedAt: isset($data['verified_at']) ? (string) $data['verified_at'] : null,
            rowCounts: is_array($data['row_counts'] ?? null) ? $data['row_counts'] : [],
            offsiteCopy: is_array($data['offsite_copy'] ?? null) ? $data['offsite_copy'] : [],
            schemaVersion: (int) ($data['schema_version'] ?? 1),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'backup_id' => $this->backupId,
            'created_at' => $this->createdAt,
            'application_environment' => $this->applicationEnvironment,
            'application_git_sha' => $this->applicationGitSha,
            'database_engine' => $this->databaseEngine,
            'database_name' => $this->databaseName,
            'database_host' => $this->databaseHost,
            'database_port' => $this->databasePort,
            'database_server_version' => $this->databaseServerVersion,
            'backup_filename' => $this->backupFilename,
            'backup_format' => $this->backupFormat,
            'backup_size_bytes' => $this->backupSizeBytes,
            'sha256' => $this->sha256,
            'purpose' => $this->purpose,
            'verification_status' => $this->verificationStatus,
            'verified_at' => $this->verifiedAt,
            'row_counts' => $this->rowCounts,
            'offsite_copy' => $this->offsiteCopy,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
