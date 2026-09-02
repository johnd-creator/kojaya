<?php

namespace App\Services\Security;

use App\Enums\DocumentCleanupState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmployeeDocumentStorage
{
    public const DISK = 'employee_documents';

    public const LEGACY_DISK = 'public';

    public const PREFIX_CERTIFICATES = 'certificates';

    public const PREFIX_MCU = 'mcu';

    /**
     * Permitted root prefixes.
     */
    public const ALLOWED_PREFIXES = [
        self::PREFIX_CERTIFICATES,
        self::PREFIX_MCU,
    ];

    /**
     * Allowed MIME types for sensitive employee documents.
     */
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    /**
     * Store a new certificate privately for an employee.
     */
    public function storeCertificate(UploadedFile $file, string|int $employeeId): string
    {
        return $this->store($file, self::PREFIX_CERTIFICATES, (string) $employeeId);
    }

    /**
     * Store a new medical checkup document privately for an employee.
     */
    public function storeMcu(UploadedFile $file, string|int $employeeId): string
    {
        return $this->store($file, self::PREFIX_MCU, (string) $employeeId);
    }

    /**
     * Store a file under the given prefix and employee directory on the private disk.
     */
    public function store(UploadedFile $file, string $prefix, string|int $employeeId): string
    {
        $employeeIdStr = (string) $employeeId;
        $this->validatePrefixAndEmployeeId($prefix, $employeeIdStr);

        $path = $file->store($prefix.'/'.$employeeIdStr, self::DISK);

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException('Failed to write employee document to private storage.');
        }

        $this->validateOwnedPath($path, $prefix, $employeeIdStr);

        return $path;
    }

    /**
     * Replace document safely with verified rollback safety:
     * 1. Validate previous path ownership if provided.
     * 2. Capture byte size and SHA-256 integrity evidence of previous document.
     * 3. Write new file to private disk and verify write.
     * 4. Execute DB update callback ($onUpdateDb).
     * 5. If DB update fails, clean up new file using verified deletion and rethrow.
     * 6. Clean up previous document:
     *    - Materialize and verify a private safety copy for legacy-only documents before public cleanup.
     *    - If public cleanup fails or is unknown, preserve the private safety copy so rollback does not depend on public availability.
     *    - If cleanup is confirmed_absent: return newPath (clean success).
     *    - If cleanup is confirmed_present or unknown: check if a valid old copy is positively confirmed present, readable, non-empty, and matching integrity evidence (size + SHA-256).
     *    - When old copy is confirmed present and readable: roll DB back to previousPath, perform verified deletion of newPath, and report any unresolved orphan.
     *    - When old copy integrity is invalid, 0-byte, corrupt, or missing: preserve DB on newPath and retain newPath on private disk to prevent document loss.
     */
    public function replace(
        UploadedFile $file,
        string $prefix,
        string|int $employeeId,
        ?string $previousPath,
        callable $onUpdateDb,
        ?callable $onRollbackDb = null
    ): string {
        $employeeIdStr = (string) $employeeId;
        $this->validatePrefixAndEmployeeId($prefix, $employeeIdStr);

        $previousEvidence = null;
        if ($previousPath) {
            $this->validateOwnedPath($previousPath, $prefix, $employeeIdStr);
            $previousEvidence = $this->captureDocumentEvidence($previousPath, $prefix, $employeeIdStr);
        }

        $newPath = $this->store($file, $prefix, $employeeIdStr);

        try {
            $onUpdateDb($newPath);
        } catch (Throwable $dbUpdateException) {
            $cleanResult = $this->deleteFileFromDisk(Storage::disk(self::DISK), $newPath);
            if (! $cleanResult['state']->isConfirmedAbsent()) {
                $orphanException = $cleanResult['exception'] ?? $dbUpdateException;
                $orphanMessage = "Database update failed for new document [{$newPath}] and failed to verify deletion of discarded new document from private storage (unresolved private orphan).";
                throw new RuntimeException($orphanMessage, previous: $dbUpdateException);
            }

            throw $dbUpdateException;
        }

        if ($previousPath && $previousPath !== $newPath) {
            $cleanupResult = $this->cleanupPreviousDocument($previousPath, $prefix, $employeeIdStr);
            $cleanupState = $cleanupResult['state'];
            $cleanupException = $cleanupResult['exception'];

            if ($cleanupState->isConfirmedAbsent()) {
                return $newPath;
            }

            // Cleanup failed or is ambiguous. Check if an old copy remains confirmed present, readable, and integrity-verified.
            // Mandatory evidence requirement: previousEvidence === null strictly prohibits DB rollback.
            $oldConfirmed = false;
            if ($previousEvidence !== null) {
                $oldConfirmed = $this->isConfirmedPresentAndReadable(
                    $previousPath,
                    $prefix,
                    $employeeIdStr,
                    $previousEvidence,
                    requireEvidence: true
                );
            }

            if ($oldConfirmed) {
                $reverted = false;
                try {
                    if ($onRollbackDb) {
                        $onRollbackDb($previousPath);
                    } else {
                        $onUpdateDb($previousPath);
                    }
                    $reverted = true;
                } catch (Throwable $rollbackException) {
                    // DB rollback callback itself failed!
                    // DB still points to newPath (or is in an error state).
                    // Keep newPath on private disk to prevent total document loss.
                    throw new RuntimeException("Failed to clean up previous document [{$previousPath}] and DB rollback failed: {$rollbackException->getMessage()}", previous: $rollbackException);
                }

                if ($reverted) {
                    // Verified deletion of newPath after successful DB rollback
                    $newCleanResult = $this->deleteFileFromDisk(Storage::disk(self::DISK), $newPath);

                    if (! $newCleanResult['state']->isConfirmedAbsent()) {
                        $orphanException = $newCleanResult['exception'] ?? $cleanupException;
                        $orphanMessage = "Document database reference safely rolled back to previous document [{$previousPath}], but failed to verify deletion of discarded new document [{$newPath}] from private storage (unresolved private orphan).";
                        throw new RuntimeException($orphanMessage, previous: $orphanException);
                    }

                    if ($cleanupException instanceof Throwable) {
                        throw $cleanupException;
                    }

                    throw new RuntimeException("Failed to securely delete document file for path [{$previousPath}]. Database reference rolled back to previous document.");
                }
            } else {
                // Old file existence is false, 0-byte, corrupted, or cannot be established.
                // Keep DB reference on newPath, keep newPath on private disk.
                // Never delete the only confirmed valid document copy.
                $message = "Ambiguous or incomplete cleanup of previous document [{$previousPath}]. Retaining new document [{$newPath}] in database and private storage to prevent data loss.";
                if ($cleanupException instanceof Throwable) {
                    throw new RuntimeException($message.': '.$cleanupException->getMessage(), previous: $cleanupException);
                }

                throw new RuntimeException($message);
            }
        }

        return $newPath;
    }

    /**
     * Capture byte size and SHA-256 integrity evidence of an existing document before cleanup.
     * Rejects 0-byte, empty, or unreadable files.
     *
     * @return array{size: int, sha256: string, disk: string}|null
     */
    public function captureDocumentEvidence(
        string $path,
        string $expectedPrefix,
        string|int $expectedEmployeeId
    ): ?array {
        try {
            $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);

            // Check private disk first
            $privateDisk = Storage::disk(self::DISK);
            try {
                if ($privateDisk->exists($path)) {
                    $content = $privateDisk->get($path);
                    if (is_string($content) && strlen($content) > 0) {
                        return [
                            'size' => strlen($content),
                            'sha256' => hash('sha256', $content),
                            'disk' => self::DISK,
                        ];
                    }
                }
            } catch (Throwable) {
                // Private check or read failed
            }

            // Fall back to legacy public disk
            $publicDisk = Storage::disk(self::LEGACY_DISK);
            try {
                if ($publicDisk->exists($path)) {
                    $content = $publicDisk->get($path);
                    if (is_string($content) && strlen($content) > 0) {
                        return [
                            'size' => strlen($content),
                            'sha256' => hash('sha256', $content),
                            'disk' => self::LEGACY_DISK,
                        ];
                    }
                }
            } catch (Throwable) {
                // Public check or read failed
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Clean up previous document across legacy public and private storage.
     * Materializes and verifies a private safety copy of legacy-only files prior to public deletion.
     * Preserves the materialized private safety copy if public cleanup fails or is ambiguous.
     *
     * @return array{state: DocumentCleanupState, exception: ?Throwable}
     */
    public function cleanupPreviousDocument(string $previousPath, string $prefix, string $employeeIdStr): array
    {
        $this->validateOwnedPath($previousPath, $prefix, $employeeIdStr);

        $privateDisk = Storage::disk(self::DISK);
        $publicDisk = Storage::disk(self::LEGACY_DISK);

        // Pre-check presence on public disk: ConfirmedPresent, ConfirmedAbsent, or Unknown
        $publicPresence = DocumentCleanupState::Unknown;
        try {
            $publicPresence = $publicDisk->exists($previousPath)
                ? DocumentCleanupState::ConfirmedPresent
                : DocumentCleanupState::ConfirmedAbsent;
        } catch (Throwable) {
            $publicPresence = DocumentCleanupState::Unknown;
        }

        // Pre-check presence on private disk: ConfirmedPresent, ConfirmedAbsent, or Unknown
        $privatePresence = DocumentCleanupState::Unknown;
        try {
            $privatePresence = $privateDisk->exists($previousPath)
                ? DocumentCleanupState::ConfirmedPresent
                : DocumentCleanupState::ConfirmedAbsent;
        } catch (Throwable) {
            $privatePresence = DocumentCleanupState::Unknown;
        }

        // Materialize and verify a private safety copy of a legacy-only previous document before attempting public cleanup
        $materializedPrivate = false;
        if ($publicPresence !== DocumentCleanupState::ConfirmedAbsent && ! $privatePresence->isConfirmedPresent()) {
            try {
                $content = $publicDisk->get($previousPath);
                if (is_string($content) && strlen($content) > 0) {
                    $written = $privateDisk->put($previousPath, $content);
                    if ($written && $privateDisk->exists($previousPath)) {
                        $readBack = $privateDisk->get($previousPath);
                        if ($readBack === $content) {
                            $materializedPrivate = true;
                            $privatePresence = DocumentCleanupState::ConfirmedPresent;
                        }
                    }
                }
            } catch (Throwable) {
                // Materialization failed; continue with independent cleanup
            }
        }

        // 1. Delete from legacy public disk if present or unknown
        $publicResult = [
            'state' => DocumentCleanupState::ConfirmedAbsent,
            'exception' => null,
        ];
        if ($publicPresence !== DocumentCleanupState::ConfirmedAbsent) {
            $publicResult = $this->deleteFileFromDisk($publicDisk, $previousPath);
        }

        // 2. Delete from private disk ONLY if public cleanup reached ConfirmedAbsent.
        // If public cleanup failed or is unknown (ConfirmedPresent or Unknown), preserve the private copy!
        $privateResult = [
            'state' => DocumentCleanupState::ConfirmedAbsent,
            'exception' => null,
        ];

        $publicState = $publicResult['state'];

        if ($publicState->isConfirmedAbsent()) {
            $shouldCleanPrivate = $materializedPrivate || $privatePresence !== DocumentCleanupState::ConfirmedAbsent;
            if ($shouldCleanPrivate) {
                $privateResult = $this->deleteFileFromDisk($privateDisk, $previousPath);
            }
        } else {
            // Public cleanup failed or is unknown.
            // Preserve the private safety copy if it was present or materialized!
            if ($materializedPrivate || $privatePresence->isConfirmedPresent()) {
                $privateResult = [
                    'state' => DocumentCleanupState::ConfirmedPresent,
                    'exception' => null,
                ];
            } else {
                $privateResult = [
                    'state' => $privatePresence,
                    'exception' => null,
                ];
            }
        }

        $privateState = $privateResult['state'];

        if ($publicState->isConfirmedAbsent() && $privateState->isConfirmedAbsent()) {
            return [
                'state' => DocumentCleanupState::ConfirmedAbsent,
                'exception' => null,
            ];
        }

        $exception = $publicResult['exception'] ?? $privateResult['exception'];

        if ($publicState->isAmbiguous() || $privateState->isAmbiguous()) {
            return [
                'state' => DocumentCleanupState::Unknown,
                'exception' => $exception,
            ];
        }

        return [
            'state' => DocumentCleanupState::ConfirmedPresent,
            'exception' => $exception,
        ];
    }

    /**
     * Delete a file from a specified disk and determine its explicit cleanup state.
     *
     * @return array{state: DocumentCleanupState, exception: ?Throwable}
     */
    public function deleteFileFromDisk(mixed $disk, string $path): array
    {
        // 1. Check if file exists before deletion
        try {
            if (! $disk->exists($path)) {
                return [
                    'state' => DocumentCleanupState::ConfirmedAbsent,
                    'exception' => null,
                ];
            }
        } catch (Throwable) {
            // Pre-check failed; continue to attempt delete
        }

        // 2. Attempt deletion
        $deleteException = null;
        try {
            $deleteSuccess = (bool) $disk->delete($path);
            if (! $deleteSuccess) {
                $deleteException = new RuntimeException("Storage driver returned false when deleting path [{$path}].");
            }
        } catch (Throwable $e) {
            $deleteException = $e;
        }

        // 3. Post-delete verification
        try {
            $existsAfter = $disk->exists($path);
            if (! $existsAfter) {
                return [
                    'state' => DocumentCleanupState::ConfirmedAbsent,
                    'exception' => null,
                ];
            }

            // File exists according to exists() check; verify readability
            try {
                $content = $disk->get($path);
                if ($content !== false && $content !== null) {
                    return [
                        'state' => DocumentCleanupState::ConfirmedPresent,
                        'exception' => $deleteException ?: new RuntimeException("Document file still exists after deletion attempt for path [{$path}]."),
                    ];
                }
            } catch (Throwable) {
                return [
                    'state' => DocumentCleanupState::Unknown,
                    'exception' => $deleteException,
                ];
            }

            return [
                'state' => DocumentCleanupState::ConfirmedPresent,
                'exception' => $deleteException ?: new RuntimeException("Document file still exists after deletion attempt for path [{$path}]."),
            ];
        } catch (Throwable $postExistsException) {
            // Post-delete verification threw: ambiguous state!
            return [
                'state' => DocumentCleanupState::Unknown,
                'exception' => $postExistsException,
            ];
        }
    }

    /**
     * Safely delete a file from private storage (and legacy public storage if present).
     * Validates ownership and fails closed if deletion cannot be confirmed absent.
     */
    public function delete(?string $path, ?string $expectedPrefix = null, string|int|null $expectedEmployeeId = null): void
    {
        if (! $path) {
            return;
        }

        if ($expectedPrefix !== null && $expectedEmployeeId !== null) {
            $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);
        } else {
            $this->validatePath($path);
        }

        $publicDisk = Storage::disk(self::LEGACY_DISK);
        $publicResult = $this->deleteFileFromDisk($publicDisk, $path);
        if (! $publicResult['state']->isConfirmedAbsent()) {
            throw $publicResult['exception'] ?: new RuntimeException("Failed to securely delete public document file for path [{$path}].");
        }

        $privateDisk = Storage::disk(self::DISK);
        $privateResult = $this->deleteFileFromDisk($privateDisk, $path);
        if (! $privateResult['state']->isConfirmedAbsent()) {
            throw $privateResult['exception'] ?: new RuntimeException("Failed to securely delete private document file for path [{$path}].");
        }
    }

    /**
     * Positively confirm that at least one valid copy of the document remains present, readable, non-empty,
     * and matches captured integrity evidence (SHA-256 and size) before rollback.
     * Rejects null, false, empty string, zero-byte content, and hash mismatches.
     *
     * @param  array{size?: int, sha256?: string}|null  $expectedEvidence
     */
    public function isConfirmedPresentAndReadable(
        string $path,
        ?string $expectedPrefix = null,
        string|int|null $expectedEmployeeId = null,
        ?array $expectedEvidence = null,
        bool $requireEvidence = false
    ): bool {
        if ($requireEvidence && $expectedEvidence === null) {
            return false;
        }

        try {
            if ($expectedPrefix !== null && $expectedEmployeeId !== null) {
                $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);
            } else {
                $this->validatePath($path);
            }

            $expectedSha256 = $expectedEvidence['sha256'] ?? null;
            $expectedSize = $expectedEvidence['size'] ?? null;

            // Check private disk first
            $privateDisk = Storage::disk(self::DISK);
            try {
                if ($privateDisk->exists($path)) {
                    $content = $privateDisk->get($path);
                    if (is_string($content) && strlen($content) > 0) {
                        $valid = true;
                        if ($expectedSize !== null && strlen($content) !== $expectedSize) {
                            $valid = false;
                        }
                        if ($valid && $expectedSha256 !== null && hash('sha256', $content) !== $expectedSha256) {
                            $valid = false;
                        }
                        if ($valid) {
                            return true;
                        }
                    }
                }
            } catch (Throwable) {
                // Private check or read failed
            }

            // Check legacy public disk
            $publicDisk = Storage::disk(self::LEGACY_DISK);
            try {
                if ($publicDisk->exists($path)) {
                    $content = $publicDisk->get($path);
                    if (is_string($content) && strlen($content) > 0) {
                        $valid = true;
                        if ($expectedSize !== null && strlen($content) !== $expectedSize) {
                            $valid = false;
                        }
                        if ($valid && $expectedSha256 !== null && hash('sha256', $content) !== $expectedSha256) {
                            $valid = false;
                        }
                        if ($valid) {
                            return true;
                        }
                    }
                }
            } catch (Throwable) {
                // Public check or read failed
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Resolve and return a download response with secure headers and verified MIME.
     */
    public function download(
        string $path,
        ?string $filename = null,
        ?string $expectedPrefix = null,
        string|int|null $expectedEmployeeId = null
    ): StreamedResponse|Response {
        if ($expectedPrefix !== null && $expectedEmployeeId !== null) {
            $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);
        } else {
            $this->validatePath($path);
        }

        $disk = $this->resolveDiskForPath($path, $expectedPrefix, $expectedEmployeeId);

        if (! $disk) {
            abort(404, 'Document file not found.');
        }

        // MIME validation: inspect actual file magic bytes and allow only whitelisted types; otherwise fallback to application/octet-stream
        $actualMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $contents = Storage::disk($disk)->get($path);
                $actualMime = finfo_buffer($finfo, (string) $contents);
                finfo_close($finfo);
            }
        }

        if (! $actualMime) {
            $actualMime = Storage::disk($disk)->mimeType($path);
        }

        $mimeType = in_array($actualMime, self::ALLOWED_MIMES, true) ? $actualMime : 'application/octet-stream';

        $downloadFilename = $filename ?: basename($path);

        return Storage::disk($disk)->download($path, $downloadFilename, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Check if a document exists on private or legacy disk.
     */
    public function exists(string $path, ?string $expectedPrefix = null, string|int|null $expectedEmployeeId = null): bool
    {
        try {
            if ($expectedPrefix !== null && $expectedEmployeeId !== null) {
                $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);
            } else {
                $this->validatePath($path);
            }

            return (bool) $this->resolveDiskForPath($path, $expectedPrefix, $expectedEmployeeId);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Determine which disk contains the document (prefers private, falls back to legacy public during migration).
     */
    public function resolveDiskForPath(string $path, ?string $expectedPrefix = null, string|int|null $expectedEmployeeId = null): ?string
    {
        if ($expectedPrefix !== null && $expectedEmployeeId !== null) {
            $this->validateOwnedPath($path, $expectedPrefix, (string) $expectedEmployeeId);
        } else {
            $this->validatePath($path);
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            return self::LEGACY_DISK;
        }

        return null;
    }

    /**
     * Ownership-aware validation.
     */
    public function validateOwnedPath(string $path, string $expectedPrefix, string|int $expectedEmployeeId): void
    {
        $this->validatePath($path);

        $employeeIdStr = (string) $expectedEmployeeId;
        $segments = explode('/', trim($path));

        $prefix = $segments[0];
        if ($prefix !== $expectedPrefix) {
            throw new InvalidArgumentException("Document path prefix [{$prefix}] does not match expected prefix [{$expectedPrefix}].");
        }

        $employeeIdSegment = $segments[1];
        if ($employeeIdSegment !== $employeeIdStr) {
            throw new InvalidArgumentException("Document path employee ID [{$employeeIdSegment}] does not match expected employee ID [{$employeeIdStr}].");
        }
    }

    /**
     * Validate path for path traversal, absolute path, empty segments, or invalid directory prefix.
     */
    public function validatePath(string $path): void
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Document path cannot be empty.');
        }

        if (str_contains($trimmed, '..') || str_contains($trimmed, '\\') || str_starts_with($trimmed, '/') || preg_match('/^[a-zA-Z]:/', $trimmed)) {
            throw new InvalidArgumentException('Invalid document path format or directory traversal detected.');
        }

        $segments = explode('/', $trimmed);
        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Document path does not match required structure {prefix}/{employeeId}/{filename}.');
        }

        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new InvalidArgumentException('Document path contains empty or invalid directory segments.');
        }

        $prefix = $segments[0];
        if (! in_array($prefix, self::ALLOWED_PREFIXES, true)) {
            throw new InvalidArgumentException("Prefix [{$prefix}] is not allowed for employee documents.");
        }

        $employeeId = $segments[1];
        if ($employeeId === '' || ! preg_match('/^[a-zA-Z0-9_-]+$/', $employeeId)) {
            throw new InvalidArgumentException('Invalid employee ID in document path.');
        }

        $filename = $segments[2];
        if ($filename === '' || ! preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$/', $filename)) {
            throw new InvalidArgumentException('Invalid filename in document path.');
        }
    }

    private function validatePrefixAndEmployeeId(string $prefix, string $employeeId): void
    {
        if (! in_array($prefix, self::ALLOWED_PREFIXES, true)) {
            throw new InvalidArgumentException("Prefix [{$prefix}] is not allowed for employee documents.");
        }

        $trimmedId = trim($employeeId);
        if ($trimmedId === '' || str_contains($trimmedId, '..') || str_contains($trimmedId, '/') || str_contains($trimmedId, '\\')) {
            throw new InvalidArgumentException('Invalid employee ID.');
        }
    }
}
