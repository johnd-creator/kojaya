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
     * Allowed mime types to extension mappings.
     */
    public const MIME_MAP = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    /**
     * Store a new certificate privately for an employee.
     */
    public function storeCertificate(UploadedFile $file, string $employeeId): string
    {
        return $this->store($file, self::PREFIX_CERTIFICATES, $employeeId);
    }

    /**
     * Store a new medical checkup document privately for an employee.
     */
    public function storeMcu(UploadedFile $file, string $employeeId): string
    {
        return $this->store($file, self::PREFIX_MCU, $employeeId);
    }

    /**
     * Store a file under the given prefix and employee directory on the private disk.
     */
    public function store(UploadedFile $file, string $prefix, string $employeeId): string
    {
        $this->validatePrefixAndEmployeeId($prefix, $employeeId);

        $path = $file->store($prefix.'/'.$employeeId, self::DISK);

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException('Failed to write employee document to private storage.');
        }

        return $path;
    }

    /**
     * Replace document safely:
     * 1. write new file to private disk
     * 2. verify the write
     * 3. execute DB update callback ($onUpdateDb)
     * 4. remove previous file only after DB update succeeds
     * 5. remove newly created orphan if DB update fails
     */
    public function replace(
        UploadedFile $file,
        string $prefix,
        string $employeeId,
        ?string $previousPath,
        callable $onUpdateDb
    ): string {
        $this->validatePrefixAndEmployeeId($prefix, $employeeId);

        $newPath = $this->store($file, $prefix, $employeeId);

        try {
            $onUpdateDb($newPath);
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($newPath);
            throw $e;
        }

        if ($previousPath && $previousPath !== $newPath) {
            $this->delete($previousPath);
        }

        return $newPath;
    }

    /**
     * Safely delete a file from private storage (and legacy public storage if present).
     */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $this->validatePath($path);

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            Storage::disk(self::LEGACY_DISK)->delete($path);
        }
    }

    /**
     * Resolve and return a download response with secure headers.
     */
    public function download(string $path, ?string $filename = null): StreamedResponse|Response
    {
        $this->validatePath($path);

        $disk = $this->resolveDiskForPath($path);

        if (! $disk) {
            abort(404, 'Document file not found.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = self::MIME_MAP[$extension] ?? (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream');

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
    public function exists(string $path): bool
    {
        try {
            $this->validatePath($path);

            return (bool) $this->resolveDiskForPath($path);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Determine which disk contains the document (prefers private, falls back to legacy public during migration).
     */
    public function resolveDiskForPath(string $path): ?string
    {
        $this->validatePath($path);

        if (Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            return self::LEGACY_DISK;
        }

        return null;
    }

    /**
     * Validate path for path traversal, absolute path, or invalid directory prefix.
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
        if (count($segments) < 3) {
            throw new InvalidArgumentException('Document path does not match required structure {prefix}/{employeeId}/{filename}.');
        }

        $prefix = $segments[0];
        if (! in_array($prefix, self::ALLOWED_PREFIXES, true)) {
            throw new InvalidArgumentException("Prefix [{$prefix}] is not allowed for employee documents.");
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
