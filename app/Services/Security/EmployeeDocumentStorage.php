<?php

namespace App\Services\Security;

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
     * Replace document safely:
     * 1. validate previous path ownership if provided
     * 2. write new file to private disk
     * 3. verify the write
     * 4. execute DB update callback ($onUpdateDb)
     * 5. remove previous file only after DB update succeeds
     * 6. remove newly created orphan if DB update fails
     */
    public function replace(
        UploadedFile $file,
        string $prefix,
        string|int $employeeId,
        ?string $previousPath,
        callable $onUpdateDb
    ): string {
        $employeeIdStr = (string) $employeeId;
        $this->validatePrefixAndEmployeeId($prefix, $employeeIdStr);

        if ($previousPath) {
            $this->validateOwnedPath($previousPath, $prefix, $employeeIdStr);
        }

        $newPath = $this->store($file, $prefix, $employeeIdStr);

        try {
            $onUpdateDb($newPath);
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($newPath);
            throw $e;
        }

        if ($previousPath && $previousPath !== $newPath) {
            $this->delete($previousPath, $prefix, $employeeIdStr);
        }

        return $newPath;
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

        // 1. Delete and verify legacy public copy first
        $publicDisk = Storage::disk(self::LEGACY_DISK);
        if ($publicDisk->exists($path)) {
            $deletedPublic = $publicDisk->delete($path);
            if (! $deletedPublic || $publicDisk->exists($path)) {
                throw new RuntimeException("Failed to securely delete public document file for path [{$path}].");
            }
        }

        // 2. Delete and verify private copy
        $privateDisk = Storage::disk(self::DISK);
        if ($privateDisk->exists($path)) {
            $deletedPrivate = $privateDisk->delete($path);
            if (! $deletedPrivate || $privateDisk->exists($path)) {
                throw new RuntimeException("Failed to securely delete private document file for path [{$path}].");
            }
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
        if ($employeeId === '' || str_contains($employeeId, '..') || str_contains($employeeId, '/') || str_contains($employeeId, '\\')) {
            throw new InvalidArgumentException('Invalid employee ID in document path.');
        }

        $filename = $segments[2];
        if ($filename === '' || str_contains($filename, '/') || str_contains($filename, '\\') || $filename === '.' || $filename === '..') {
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
