<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Security\PiiCryptoService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MemberImportValidator
{
    /**
     * Exact 12 canonical headers in exact order (V1).
     *
     * @var list<string>
     */
    public const CANONICAL_HEADERS_V1 = [
        'member_number',
        'full_name',
        'email',
        'phone_number',
        'identity_number',
        'gender',
        'company_code',
        'employee_number',
        'address',
        'membership_type',
        'join_date',
        'notes',
    ];

    /**
     * Exact 4 financial headers appended in V2 contract.
     *
     * @var list<string>
     */
    public const FINANCIAL_HEADERS = [
        'opening_balance_pokok',
        'opening_balance_wajib',
        'opening_balance_sukarela',
        'opening_balance_khusus',
    ];

    /**
     * Exact 16 canonical headers in exact order (V2).
     *
     * @var list<string>
     */
    public const CANONICAL_HEADERS_V2 = [
        ...self::CANONICAL_HEADERS_V1,
        ...self::FINANCIAL_HEADERS,
    ];

    /**
     * Backwards-compatible canonical headers default (12 columns).
     *
     * @var list<string>
     */
    public const CANONICAL_HEADERS = self::CANONICAL_HEADERS_V1;

    public const CODE_MISSING_REQUIRED_FIELD = 'MISSING_REQUIRED_FIELD';

    public const CODE_INVALID_CONTROLLED_VALUE = 'INVALID_CONTROLLED_VALUE';

    public const CODE_DUPLICATE_EMAIL_BATCH = 'DUPLICATE_EMAIL_BATCH';

    public const CODE_EMAIL_ALREADY_EXISTS = 'EMAIL_ALREADY_EXISTS';

    public const CODE_DUPLICATE_IDENTITY_NUMBER_BATCH = 'DUPLICATE_IDENTITY_NUMBER_BATCH';

    public const CODE_IDENTITY_NUMBER_ALREADY_EXISTS = 'IDENTITY_NUMBER_ALREADY_EXISTS';

    public const CODE_DUPLICATE_MEMBER_NUMBER_BATCH = 'DUPLICATE_MEMBER_NUMBER_BATCH';

    public const CODE_MEMBER_NUMBER_ALREADY_EXISTS = 'MEMBER_NUMBER_ALREADY_EXISTS';

    public const CODE_UNRESOLVED_EMPLOYEE_REFERENCE = 'UNRESOLVED_EMPLOYEE_REFERENCE';

    public const CODE_EMPLOYEE_REFERENCE_CONFLICT = 'EMPLOYEE_REFERENCE_CONFLICT';

    public const CODE_REFERENCE_CONTEXT_UNRESOLVED = 'REFERENCE_CONTEXT_UNRESOLVED';

    public const CODE_INVALID_HEADER = 'INVALID_HEADER';

    public const CODE_INVALID_FINANCIAL_VALUE = 'INVALID_FINANCIAL_VALUE';

    public const CODE_MISSING_CUTOFF_DATE = 'MISSING_CUTOFF_DATE';

    public const CODE_INVALID_CUTOFF_DATE = 'INVALID_CUTOFF_DATE';

    public const CODE_CUTOFF_DATE_IN_FUTURE = 'CUTOFF_DATE_IN_FUTURE';

    public const CODE_JOIN_DATE_AFTER_CUTOFF = 'JOIN_DATE_AFTER_CUTOFF';

    public const CODE_AMBIGUOUS_CONTRIBUTION_TYPE = 'AMBIGUOUS_CONTRIBUTION_TYPE';

    public const EMPLOYEE_RESOLUTION_NOT_PROVIDED = 'NOT_PROVIDED';

    public const EMPLOYEE_RESOLUTION_RESOLVED = 'RESOLVED';

    public const EMPLOYEE_RESOLUTION_UNRESOLVED = 'UNRESOLVED';

    public const EMPLOYEE_RESOLUTION_CONFLICT = 'CONFLICT';

    public function __construct(
        private readonly ?PiiCryptoService $piiCrypto = null,
    ) {}

    /**
     * Dispatcher to validate file path, CSV string, or rows array.
     *
     * @param  mixed  $input  File path, raw CSV string, or array of rows
     * @param  array<string, mixed>  $context  Validation context (e.g. organization_id, import_date)
     */
    public function validate(mixed $input, array $context = []): ImportValidationResult
    {
        if (is_string($input)) {
            if (str_contains($input, "\n") || str_contains($input, "\r")) {
                return $this->validateCsv($input, $context);
            }

            if (file_exists($input) && is_file($input)) {
                return $this->validateFile($input, $context);
            }

            return $this->validateCsv($input, $context);
        }

        if (is_array($input)) {
            $headers = $context['headers'] ?? [];

            return $this->validateRows($input, is_array($headers) ? $headers : [], $context);
        }

        throw new InvalidArgumentException('Input must be a file path, CSV content string, or an array of rows.');
    }

    /**
     * Validate CSV file from filesystem.
     *
     * @param  array<string, mixed>  $context
     */
    public function validateFile(string $filePath, array $context = []): ImportValidationResult
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [
                    new ImportValidationError(
                        row: null,
                        field: 'file',
                        code: self::CODE_INVALID_HEADER,
                        message: "Berkas impor tidak ditemukan atau tidak dapat dibaca: {$filePath}.",
                        severity: ImportValidationError::SEVERITY_FATAL,
                    ),
                ],
                rows: [],
            );
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [
                    new ImportValidationError(
                        row: null,
                        field: 'file',
                        code: self::CODE_INVALID_HEADER,
                        message: "Gagal membaca konten berkas impor: {$filePath}.",
                        severity: ImportValidationError::SEVERITY_FATAL,
                    ),
                ],
                rows: [],
            );
        }

        return $this->validateCsv($content, $context);
    }

    /**
     * Validate raw CSV string content.
     *
     * @param  array<string, mixed>  $context
     */
    public function validateCsv(string $csvContent, array $context = []): ImportValidationResult
    {
        // Strip UTF-8 BOM if present
        if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
            $csvContent = substr($csvContent, 3);
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [
                    new ImportValidationError(
                        row: null,
                        field: 'stream',
                        code: self::CODE_INVALID_HEADER,
                        message: 'Gagal membuka memory stream untuk membaca CSV.',
                        severity: ImportValidationError::SEVERITY_FATAL,
                    ),
                ],
                rows: [],
            );
        }

        fwrite($stream, $csvContent);
        rewind($stream);

        // Read header row
        $headerRow = fgetcsv($stream);
        if ($headerRow === false || $headerRow === null || $headerRow === [null] || $headerRow === ['']) {
            fclose($stream);

            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [
                    new ImportValidationError(
                        row: null,
                        field: 'header',
                        code: self::CODE_INVALID_HEADER,
                        message: 'Berkas CSV kosong atau tidak memiliki baris header.',
                        severity: ImportValidationError::SEVERITY_FATAL,
                    ),
                ],
                rows: [],
            );
        }

        // Validate header
        $headerCheck = $this->validateHeaderRow($headerRow);
        if ($headerCheck['error'] !== null) {
            fclose($stream);

            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [$headerCheck['error']],
                rows: [],
                csvVersion: $headerCheck['version'],
            );
        }

        // Read data rows
        $rawRows = [];
        while (($row = fgetcsv($stream)) !== false) {
            // Ignore completely empty lines at EOF or between lines
            if ($row === [null] || $row === [''] || $row === []) {
                continue;
            }
            $rawRows[] = $row;
        }

        fclose($stream);

        return $this->processRows($headerCheck['version'], $headerCheck['headers'], $rawRows, $context);
    }

    /**
     * Validate an array of rows directly.
     *
     * @param  array<int, mixed>  $rows
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $context
     */
    public function validateRows(array $rows, array $headers = [], array $context = []): ImportValidationResult
    {
        // If headers not explicitly provided, inspect the first row
        if ($headers === []) {
            if ($rows === []) {
                return new ImportValidationResult(
                    valid: false,
                    headerValid: false,
                    totalRows: 0,
                    validRows: 0,
                    invalidRows: 0,
                    errors: [
                        new ImportValidationError(
                            row: null,
                            field: 'header',
                            code: self::CODE_INVALID_HEADER,
                            message: 'Header kolom wajib disediakan dan tidak boleh kosong.',
                            severity: ImportValidationError::SEVERITY_FATAL,
                        ),
                    ],
                    rows: [],
                );
            }

            $first = reset($rows);
            if (is_array($first) && ! array_is_list($first)) {
                $headers = array_keys($first);
            } else {
                return new ImportValidationResult(
                    valid: false,
                    headerValid: false,
                    totalRows: 0,
                    validRows: 0,
                    invalidRows: 0,
                    errors: [
                        new ImportValidationError(
                            row: null,
                            field: 'header',
                            code: self::CODE_INVALID_HEADER,
                            message: 'Header tidak ditemukan pada baris data input.',
                            severity: ImportValidationError::SEVERITY_FATAL,
                        ),
                    ],
                    rows: [],
                );
            }
        }

        $headerCheck = $this->validateHeaderRow($headers);
        if ($headerCheck['error'] !== null) {
            return new ImportValidationResult(
                valid: false,
                headerValid: false,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [$headerCheck['error']],
                rows: [],
                csvVersion: $headerCheck['version'],
            );
        }

        // Convert associative rows or list rows into indexed arrays aligned with canonical headers
        $rawRows = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_is_list($row)) {
                $rawRows[] = $row;
            } else {
                $aligned = [];
                foreach ($headerCheck['headers'] as $col) {
                    $aligned[] = $row[$col] ?? null;
                }
                $rawRows[] = $aligned;
            }
        }

        return $this->processRows($headerCheck['version'], $headerCheck['headers'], $rawRows, $context);
    }

    /**
     * Strict canonical header check: supports V1 (exact 12 cols) or V2 (exact 16 cols).
     * Exact count, exact order, exact casing.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array{error: ?ImportValidationError, version: string, headers: list<string>}
     */
    private function validateHeaderRow(array $headerRow): array
    {
        $actualCount = count($headerRow);

        if ($actualCount === count(self::CANONICAL_HEADERS_V1)) {
            $expected = self::CANONICAL_HEADERS_V1;
            $version = 'v1';
        } elseif ($actualCount === count(self::CANONICAL_HEADERS_V2)) {
            $expected = self::CANONICAL_HEADERS_V2;
            $version = 'v2';
        } else {
            return [
                'error' => new ImportValidationError(
                    row: null,
                    field: 'header',
                    code: self::CODE_INVALID_HEADER,
                    message: "Jumlah kolom header tidak sesuai kontrak. Diharapkan tepat 12 kolom (V1) atau 16 kolom (V2), ditemukan {$actualCount} kolom.",
                    severity: ImportValidationError::SEVERITY_FATAL,
                ),
                'version' => 'unknown',
                'headers' => [],
            ];
        }

        $expectedCount = count($expected);
        for ($i = 0; $i < $expectedCount; $i++) {
            $actual = $headerRow[$i];
            $expectedCol = $expected[$i];

            if ($actual !== $expectedCol) {
                return [
                    'error' => new ImportValidationError(
                        row: null,
                        field: 'header',
                        code: self::CODE_INVALID_HEADER,
                        message: 'Header kolom ke-'.($i + 1)." tidak sesuai kontrak {$version}. Diharapkan '{$expectedCol}', tetapi ditemukan '{$actual}'.",
                        severity: ImportValidationError::SEVERITY_FATAL,
                    ),
                    'version' => $version,
                    'headers' => $expected,
                ];
            }
        }

        return [
            'error' => null,
            'version' => $version,
            'headers' => $expected,
        ];
    }

    /**
     * Process parsed rows through deterministic normalization, field validation,
     * batch duplicate detection, and database conflict checks.
     *
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rawRows
     * @param  array<string, mixed>  $context
     */
    private function processRows(string $version, array $headers, array $rawRows, array $context): ImportValidationResult
    {
        $totalRows = count($rawRows);
        if ($totalRows === 0) {
            return new ImportValidationResult(
                valid: true,
                headerValid: true,
                totalRows: 0,
                validRows: 0,
                invalidRows: 0,
                errors: [],
                rows: [],
                csvVersion: $version,
            );
        }

        /** @var array<int, array<string, mixed>> $rawRowsMap */
        $rawRowsMap = [];
        /** @var array<int, array<string, mixed>> $normalizedRowsMap */
        $normalizedRowsMap = [];
        /** @var array<int, list<ImportValidationError>> $rowErrorsMap */
        $rowErrorsMap = [];
        /** @var array<int, bool> $genRequiredMap */
        $genRequiredMap = [];
        /** @var array<int, string> $employeeStatusMap */
        $employeeStatusMap = [];
        /** @var array<int, ?int> $resolvedEmployeeIdMap */
        $resolvedEmployeeIdMap = [];

        // Phase 1: Syntactic validation & Field normalization
        foreach ($rawRows as $index => $rowValues) {
            $rowNumber = $index + 1;
            $rowErrors = [];

            // Check column count for this row
            if (count($rowValues) !== count($headers)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'row',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Jumlah kolom baris ({$rowNumber}) tidak sesuai kontrak ".count($headers).' kolom.',
                );
            }

            // Combine into associative array with headers
            $rawAssoc = [];
            foreach ($headers as $colIndex => $colName) {
                $rawAssoc[$colName] = $rowValues[$colIndex] ?? null;
            }
            $rawRowsMap[$rowNumber] = $rawAssoc;

            // 1. member_number
            $rawMemberNo = $rawAssoc['member_number'] ?? null;
            $trimmedMemberNo = $rawMemberNo === null ? null : trim((string) $rawMemberNo);
            if ($trimmedMemberNo === '' || $trimmedMemberNo === null) {
                $normalizedMemberNo = null;
                $genRequired = true;
            } else {
                $upperMemberNo = strtoupper($trimmedMemberNo);
                $genRequired = false;
                if (! preg_match('/^KOP-\d{3,}$/', $upperMemberNo) || strlen($upperMemberNo) > 20) {
                    $rowErrors[] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'member_number',
                        code: self::CODE_INVALID_CONTROLLED_VALUE,
                        message: "Format nomor anggota '{$upperMemberNo}' tidak valid. Harus diawali 'KOP-' diikuti minimal 3 digit angka (contoh: KOP-001).",
                    );
                    $normalizedMemberNo = $upperMemberNo;
                } else {
                    $normalizedMemberNo = $upperMemberNo;
                }
            }
            $genRequiredMap[$rowNumber] = $genRequired;

            // 2. full_name
            $rawFullName = $rawAssoc['full_name'] ?? null;
            $normalizedFullName = $rawFullName === null ? '' : trim((string) $rawFullName);
            if ($normalizedFullName === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'full_name',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Nama lengkap wajib diisi.',
                );
            } elseif (mb_strlen($normalizedFullName) < 3) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'full_name',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: 'Nama lengkap minimal 3 karakter.',
                );
            } elseif (mb_strlen($normalizedFullName) > 100) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'full_name',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: 'Nama lengkap maksimal 100 karakter.',
                );
            }

            // 3. email
            $rawEmail = $rawAssoc['email'] ?? null;
            $trimmedEmail = $rawEmail === null ? '' : strtolower(trim((string) $rawEmail));
            $normalizedEmail = $trimmedEmail;
            if ($normalizedEmail === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'email',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Email wajib diisi.',
                );
            } elseif (! filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) || strlen($normalizedEmail) > 255) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'email',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Format email '{$normalizedEmail}' tidak valid.",
                );
            }

            // 4. phone_number
            $rawPhone = $rawAssoc['phone_number'] ?? null;
            $cleanedPhone = $rawPhone === null ? '' : preg_replace('/[\s\-\.\(\)]+/', '', (string) $rawPhone);
            if (str_starts_with($cleanedPhone, '+628')) {
                $cleanedPhone = '08'.substr($cleanedPhone, 4);
            } elseif (str_starts_with($cleanedPhone, '628')) {
                $cleanedPhone = '08'.substr($cleanedPhone, 3);
            }
            $normalizedPhone = $cleanedPhone;
            if ($normalizedPhone === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'phone_number',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Nomor telepon wajib diisi.',
                );
            } elseif (! preg_match('/^08[1-9][0-9]{7,11}$/', $normalizedPhone)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'phone_number',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Nomor telepon '{$normalizedPhone}' tidak valid. Format harus diawali 08, 628, atau +628 dengan panjang 10-14 digit.",
                );
            }

            // 5. identity_number (NIK)
            $rawNik = $rawAssoc['identity_number'] ?? null;
            $trimmedNik = $rawNik === null ? '' : trim((string) $rawNik);
            $normalizedNik = $trimmedNik;
            if ($normalizedNik === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'identity_number',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Nomor identitas (NIK) wajib diisi.',
                );
            } elseif (! preg_match('/^\d{16}$/', $normalizedNik)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'identity_number',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: 'Nomor identitas (NIK) wajib berupa tepat 16 digit angka numerik.',
                );
            }

            // 6. gender
            $rawGender = $rawAssoc['gender'] ?? null;
            $normalizedGender = $rawGender === null ? '' : strtoupper(trim((string) $rawGender));
            if ($normalizedGender === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'gender',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Jenis kelamin wajib diisi.',
                );
            } elseif (! in_array($normalizedGender, ['L', 'P'], true)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'gender',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Jenis kelamin '{$normalizedGender}' tidak valid. Pilihan yang diizinkan hanya L atau P.",
                );
            }

            // 7. company_code
            $rawCompany = $rawAssoc['company_code'] ?? null;
            $normalizedCompany = $rawCompany === null ? '' : strtoupper(trim((string) $rawCompany));
            if ($normalizedCompany === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'company_code',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Kode perusahaan wajib diisi.',
                );
            } elseif (! in_array($normalizedCompany, ['IP', 'CDB', 'KOP'], true)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'company_code',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Kode perusahaan '{$normalizedCompany}' tidak valid. Pilihan yang diizinkan hanya IP, CDB, atau KOP.",
                );
            }

            // 8. employee_number
            $rawEmp = $rawAssoc['employee_number'] ?? null;
            $trimmedEmp = $rawEmp === null ? null : trim((string) $rawEmp);
            if ($trimmedEmp === '' || $trimmedEmp === null) {
                $normalizedEmp = null;
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_NOT_PROVIDED;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            } else {
                $normalizedEmp = $trimmedEmp;
                if (! preg_match('/^[A-Za-z0-9\-]{3,30}$/', $normalizedEmp)) {
                    $rowErrors[] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'employee_number',
                        code: self::CODE_INVALID_CONTROLLED_VALUE,
                        message: "Format NIP '{$normalizedEmp}' tidak valid. Hanya boleh mengandung huruf, angka, dan tanda hubung (-).",
                    );
                    $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_UNRESOLVED;
                    $resolvedEmployeeIdMap[$rowNumber] = null;
                } else {
                    $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_UNRESOLVED;
                    $resolvedEmployeeIdMap[$rowNumber] = null;
                }
            }

            // 9. address
            $rawAddress = $rawAssoc['address'] ?? null;
            $normalizedAddress = $rawAddress === null ? '' : trim((string) $rawAddress);
            if ($normalizedAddress === '') {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'address',
                    code: self::CODE_MISSING_REQUIRED_FIELD,
                    message: 'Alamat domisili wajib diisi.',
                );
            } elseif (mb_strlen($normalizedAddress) > 1000) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'address',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: 'Alamat domisili maksimal 1000 karakter.',
                );
            }

            // 10. membership_type
            $rawMembership = $rawAssoc['membership_type'] ?? null;
            $trimmedMembership = $rawMembership === null ? '' : strtoupper(trim((string) $rawMembership));
            if ($trimmedMembership === '') {
                $normalizedMembership = 'AB'; // deterministic default
            } else {
                $normalizedMembership = $trimmedMembership;
            }
            if (! in_array($normalizedMembership, ['AB', 'ALB'], true)) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'membership_type',
                    code: self::CODE_INVALID_CONTROLLED_VALUE,
                    message: "Jenis keanggotaan '{$normalizedMembership}' tidak valid. Pilihan yang diizinkan hanya AB atau ALB.",
                );
            }

            // 11. join_date
            $rawJoinDate = $rawAssoc['join_date'] ?? null;
            $trimmedJoinDate = $rawJoinDate === null ? '' : trim((string) $rawJoinDate);
            if ($trimmedJoinDate === '') {
                $contextImportDate = $context['import_date'] ?? null;
                if ($contextImportDate === null || trim((string) $contextImportDate) === '') {
                    $rowErrors[] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'join_date',
                        code: self::CODE_MISSING_REQUIRED_FIELD,
                        message: 'Tanggal bergabung wajib diisi jika konteks tanggal impor tidak disediakan.',
                    );
                    $normalizedJoinDate = null;
                } else {
                    $importDateStr = trim((string) $contextImportDate);
                    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $importDateStr) || ! $this->isValidCalendarDate($importDateStr)) {
                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: 'join_date',
                            code: self::CODE_INVALID_CONTROLLED_VALUE,
                            message: "Konteks tanggal impor '{$importDateStr}' bukan tanggal kalender valid dengan format YYYY-MM-DD.",
                        );
                        $normalizedJoinDate = null;
                    } else {
                        $normalizedJoinDate = $importDateStr;
                    }
                }
            } else {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmedJoinDate) || ! $this->isValidCalendarDate($trimmedJoinDate)) {
                    $rowErrors[] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'join_date',
                        code: self::CODE_INVALID_CONTROLLED_VALUE,
                        message: "Format tanggal bergabung '{$trimmedJoinDate}' tidak valid. Harus berupa tanggal kalender yang valid dengan format YYYY-MM-DD.",
                    );
                    $normalizedJoinDate = null;
                } else {
                    $normalizedJoinDate = $trimmedJoinDate;
                }
            }

            // 12. notes
            $rawNotes = $rawAssoc['notes'] ?? null;
            $trimmedNotes = $rawNotes === null ? null : trim((string) $rawNotes);
            $normalizedNotes = ($trimmedNotes === '' || $trimmedNotes === null) ? null : $trimmedNotes;

            // 13-16. Financial Fields (Opening Balances)
            $pokok = 0.0;
            $wajib = 0.0;
            $sukarela = 0.0;
            $khusus = 0.0;

            if ($version === 'v2') {
                $financialMap = [
                    'opening_balance_pokok' => 'POKOK',
                    'opening_balance_wajib' => 'WAJIB',
                    'opening_balance_sukarela' => 'SUKARELA',
                    'opening_balance_khusus' => 'KHUSUS',
                ];

                foreach ($financialMap as $finField => $categoryLabel) {
                    $rawVal = $rawAssoc[$finField] ?? null;
                    $trimmedVal = $rawVal === null ? '' : trim((string) $rawVal);

                    if ($trimmedVal === '') {
                        match ($categoryLabel) {
                            'POKOK' => $pokok = 0.0,
                            'WAJIB' => $wajib = 0.0,
                            'SUKARELA' => $sukarela = 0.0,
                            'KHUSUS' => $khusus = 0.0,
                        };

                        continue;
                    }

                    // Formula injection
                    if (str_starts_with($trimmedVal, '=') || str_starts_with($trimmedVal, '@')) {
                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: $finField,
                            code: self::CODE_INVALID_FINANCIAL_VALUE,
                            message: "Formula atau formula injection tidak diizinkan pada {$finField}.",
                        );

                        continue;
                    }

                    if (str_starts_with($trimmedVal, '+')) {
                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: $finField,
                            code: self::CODE_INVALID_FINANCIAL_VALUE,
                            message: "Karakter awalan '+' tidak diizinkan pada {$finField}.",
                        );

                        continue;
                    }

                    if (str_starts_with($trimmedVal, '-') || (is_numeric($trimmedVal) && (float) $trimmedVal < 0)) {
                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: $finField,
                            code: self::CODE_INVALID_FINANCIAL_VALUE,
                            message: "Nilai saldo awal {$finField} tidak boleh negatif.",
                        );

                        continue;
                    }

                    if (! preg_match('/^\d+(\.\d{1,2})?$/', $trimmedVal)) {
                        if (preg_match('/^\d+\.\d{3,}$/', $trimmedVal)) {
                            $msg = "Nilai {$finField} ('{$trimmedVal}') memiliki lebih dari 2 angka desimal.";
                        } else {
                            $msg = "Format nilai {$finField} ('{$trimmedVal}') tidak valid. Gunakan format angka desimal murni tanpa pemisah ribuan (contoh: 150000 atau 150000.50).";
                        }

                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: $finField,
                            code: self::CODE_INVALID_FINANCIAL_VALUE,
                            message: $msg,
                        );

                        continue;
                    }

                    $canonicalVal = bcadd($trimmedVal, '0', 2);
                    if (bccomp($canonicalVal, '999999999999.99', 2) > 0) {
                        $rowErrors[] = new ImportValidationError(
                            row: $rowNumber,
                            field: $finField,
                            code: self::CODE_INVALID_FINANCIAL_VALUE,
                            message: "Nilai {$finField} melebihi batas maksimum 999.999.999.999,99.",
                        );

                        continue;
                    }

                    match ($categoryLabel) {
                        'POKOK' => $pokok = (float) $canonicalVal,
                        'WAJIB' => $wajib = (float) $canonicalVal,
                        'SUKARELA' => $sukarela = (float) $canonicalVal,
                        'KHUSUS' => $khusus = (float) $canonicalVal,
                    };
                }
            }

            $rowTotalStr = bcadd(
                bcadd(number_format($pokok, 2, '.', ''), number_format($wajib, 2, '.', ''), 2),
                bcadd(number_format($sukarela, 2, '.', ''), number_format($khusus, 2, '.', ''), 2),
                2
            );

            if (bccomp($rowTotalStr, '99999999999999.99', 2) > 0) {
                $rowErrors[] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'opening_balance_total',
                    code: self::CODE_INVALID_FINANCIAL_VALUE,
                    message: "Total akumulasi saldo awal baris {$rowNumber} melebihi batas maksimum 99.999.999.999.999,99.",
                );
            }

            $normalizedRowsMap[$rowNumber] = [
                'member_number' => $normalizedMemberNo,
                'full_name' => $normalizedFullName,
                'email' => $normalizedEmail,
                'phone_number' => $normalizedPhone,
                'identity_number' => $normalizedNik,
                'gender' => $normalizedGender,
                'company_code' => $normalizedCompany,
                'employee_number' => $normalizedEmp,
                'address' => $normalizedAddress,
                'membership_type' => $normalizedMembership,
                'join_date' => $normalizedJoinDate,
                'notes' => $normalizedNotes,
                'opening_balance_pokok' => $pokok,
                'opening_balance_wajib' => $wajib,
                'opening_balance_sukarela' => $sukarela,
                'opening_balance_khusus' => $khusus,
                'opening_balance_total' => (float) $rowTotalStr,
            ];

            $rowErrorsMap[$rowNumber] = $rowErrors;
        }

        // Phase 2: Batch Duplicates Detection (email, identity_number, member_number)
        $this->detectBatchDuplicates($normalizedRowsMap, $rowErrorsMap);

        // Phase 3: Database Conflict Checks (email, identity_number, member_number)
        $this->detectDatabaseConflicts($normalizedRowsMap, $rowErrorsMap);

        // Phase 4: Employee Reference Resolution & Conflicts
        $this->resolveEmployeeReferences(
            $normalizedRowsMap,
            $rowErrorsMap,
            $employeeStatusMap,
            $resolvedEmployeeIdMap,
            $context
        );

        // Phase 4.5: Cut-off date & Active Contribution Type Mapping Validation
        $batchErrors = [];
        $hasPositiveOpeningBalance = false;
        $positiveBalanceCount = 0;
        $sumPokok = '0.00';
        $sumWajib = '0.00';
        $sumSukarela = '0.00';
        $sumKhusus = '0.00';

        foreach ($normalizedRowsMap as $rowNumber => $rowNorm) {
            $rowP = number_format((float) ($rowNorm['opening_balance_pokok'] ?? 0), 2, '.', '');
            $rowW = number_format((float) ($rowNorm['opening_balance_wajib'] ?? 0), 2, '.', '');
            $rowS = number_format((float) ($rowNorm['opening_balance_sukarela'] ?? 0), 2, '.', '');
            $rowK = number_format((float) ($rowNorm['opening_balance_khusus'] ?? 0), 2, '.', '');
            $rowTotal = bcadd(bcadd($rowP, $rowW, 2), bcadd($rowS, $rowK, 2), 2);

            if (bccomp($rowTotal, '0.00', 2) > 0) {
                $hasPositiveOpeningBalance = true;
                $positiveBalanceCount++;
                $sumPokok = bcadd($sumPokok, $rowP, 2);
                $sumWajib = bcadd($sumWajib, $rowW, 2);
                $sumSukarela = bcadd($sumSukarela, $rowS, 2);
                $sumKhusus = bcadd($sumKhusus, $rowK, 2);
            }
        }

        $grandTotal = bcadd(bcadd($sumPokok, $sumWajib, 2), bcadd($sumSukarela, $sumKhusus, 2), 2);

        if (bccomp($grandTotal, '99999999999999.99', 2) > 0) {
            $batchErrors[] = new ImportValidationError(
                row: null,
                field: 'opening_balance_total',
                code: self::CODE_INVALID_FINANCIAL_VALUE,
                message: 'Total akumulasi saldo awal seluruh batch melebihi batas maksimum 99.999.999.999.999,99.',
                severity: ImportValidationError::SEVERITY_FATAL,
            );
        }

        $cutoffDateInput = $context['opening_balance_cutoff_date'] ?? null;
        $trimmedCutoff = $cutoffDateInput === null ? '' : trim((string) $cutoffDateInput);

        if ($hasPositiveOpeningBalance) {
            if ($trimmedCutoff === '') {
                $batchErrors[] = new ImportValidationError(
                    row: null,
                    field: 'opening_balance_cutoff_date',
                    code: self::CODE_MISSING_CUTOFF_DATE,
                    message: 'Tanggal cut-off saldo awal wajib diisi jika berkas CSV memiliki saldo awal.',
                    severity: ImportValidationError::SEVERITY_FATAL,
                );
            } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmedCutoff) || ! $this->isValidCalendarDate($trimmedCutoff)) {
                $batchErrors[] = new ImportValidationError(
                    row: null,
                    field: 'opening_balance_cutoff_date',
                    code: self::CODE_INVALID_CUTOFF_DATE,
                    message: "Format tanggal cut-off saldo awal '{$trimmedCutoff}' tidak valid. Harus berupa tanggal kalender YYYY-MM-DD.",
                    severity: ImportValidationError::SEVERITY_FATAL,
                );
            } elseif ($trimmedCutoff > now()->toDateString()) {
                $batchErrors[] = new ImportValidationError(
                    row: null,
                    field: 'opening_balance_cutoff_date',
                    code: self::CODE_CUTOFF_DATE_IN_FUTURE,
                    message: 'Tanggal cut-off saldo awal tidak boleh lebih dari hari ini.',
                    severity: ImportValidationError::SEVERITY_FATAL,
                );
            } else {
                foreach ($normalizedRowsMap as $rowNumber => $rowNorm) {
                    if (($rowNorm['opening_balance_total'] ?? 0.0) > 0) {
                        $joinDate = $rowNorm['join_date'] ?? null;
                        if ($joinDate !== null && $joinDate > $trimmedCutoff) {
                            $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                                row: $rowNumber,
                                field: 'join_date',
                                code: self::CODE_JOIN_DATE_AFTER_CUTOFF,
                                message: "Tanggal bergabung anggota ({$joinDate}) tidak boleh lebih baru dari tanggal cut-off saldo awal ({$trimmedCutoff}).",
                            );
                        }
                    }
                }
            }
        } elseif ($trimmedCutoff !== '') {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmedCutoff) || ! $this->isValidCalendarDate($trimmedCutoff)) {
                $batchErrors[] = new ImportValidationError(
                    row: null,
                    field: 'opening_balance_cutoff_date',
                    code: self::CODE_INVALID_CUTOFF_DATE,
                    message: "Format tanggal cut-off saldo awal '{$trimmedCutoff}' tidak valid. Harus berupa tanggal kalender YYYY-MM-DD.",
                    severity: ImportValidationError::SEVERITY_FATAL,
                );
            } elseif ($trimmedCutoff > now()->toDateString()) {
                $batchErrors[] = new ImportValidationError(
                    row: null,
                    field: 'opening_balance_cutoff_date',
                    code: self::CODE_CUTOFF_DATE_IN_FUTURE,
                    message: 'Tanggal cut-off saldo awal tidak boleh lebih dari hari ini.',
                    severity: ImportValidationError::SEVERITY_FATAL,
                );
            }
        }

        if ($hasPositiveOpeningBalance) {
            $categoryTotals = [
                'POKOK' => $sumPokok,
                'WAJIB' => $sumWajib,
                'SUKARELA' => $sumSukarela,
                'KHUSUS' => $sumKhusus,
            ];

            foreach ($categoryTotals as $cat => $total) {
                if ($total > 0) {
                    $matchingTypes = \App\Models\CooperativeContributionType::query()
                        ->where('category', $cat)
                        ->where('is_active', true)
                        ->get();

                    if ($matchingTypes->isEmpty()) {
                        $batchErrors[] = new ImportValidationError(
                            row: null,
                            field: 'contribution_type',
                            code: self::CODE_AMBIGUOUS_CONTRIBUTION_TYPE,
                            message: "Tidak ditemukan jenis simpanan aktif untuk kategori {$cat}.",
                            severity: ImportValidationError::SEVERITY_FATAL,
                        );
                    } elseif ($matchingTypes->count() > 1) {
                        $batchErrors[] = new ImportValidationError(
                            row: null,
                            field: 'contribution_type',
                            code: self::CODE_AMBIGUOUS_CONTRIBUTION_TYPE,
                            message: "Ditemukan {$matchingTypes->count()} jenis simpanan aktif untuk kategori {$cat}. Konfigurasi jenis simpanan ambigu.",
                            severity: ImportValidationError::SEVERITY_FATAL,
                        );
                    }
                }
            }
        }

        // Phase 5: Assemble structured results
        $resultRows = [];
        $allErrors = [];
        $validRowsCount = 0;
        $invalidRowsCount = 0;

        foreach ($rawRowsMap as $rowNumber => $rawData) {
            $normalizedData = $normalizedRowsMap[$rowNumber];
            $errors = $rowErrorsMap[$rowNumber] ?? [];
            $genRequired = $genRequiredMap[$rowNumber] ?? false;
            $empStatus = $employeeStatusMap[$rowNumber] ?? self::EMPLOYEE_RESOLUTION_NOT_PROVIDED;
            $resolvedEmpId = $resolvedEmployeeIdMap[$rowNumber] ?? null;

            $isRowValid = count($errors) === 0;
            $isPersistable = $isRowValid && ($empStatus === self::EMPLOYEE_RESOLUTION_RESOLVED || $empStatus === self::EMPLOYEE_RESOLUTION_NOT_PROVIDED);
            $isManualReviewRequired = count($errors) > 0 || $empStatus === self::EMPLOYEE_RESOLUTION_UNRESOLVED || $empStatus === self::EMPLOYEE_RESOLUTION_CONFLICT;

            if ($isRowValid) {
                $validRowsCount++;
            } else {
                $invalidRowsCount++;
            }

            foreach ($errors as $err) {
                $allErrors[] = $err;
            }

            $sanitizedRawData = $rawData;
            if (array_key_exists('identity_number', $sanitizedRawData)) {
                $sanitizedRawData['identity_number'] = '[REDACTED]';
            }

            $resultRows[] = new ImportRowResult(
                rowNumber: $rowNumber,
                valid: $isRowValid,
                rawData: $sanitizedRawData,
                normalizedData: $normalizedData,
                resolvedEmployeeId: $resolvedEmpId,
                employeeResolutionStatus: $empStatus,
                memberNumberGenerationRequired: $genRequired,
                manualReviewRequired: $isManualReviewRequired,
                persistable: $isPersistable,
                errors: $errors,
            );
        }

        foreach ($batchErrors as $bErr) {
            $allErrors[] = $bErr;
        }

        $openingBalanceSummary = [
            'version' => $version,
            'total_members' => $totalRows,
            'members_with_positive_balance_count' => $positiveBalanceCount,
            'positive_members_count' => $positiveBalanceCount,
            'total_pokok' => (float) $sumPokok,
            'total_wajib' => (float) $sumWajib,
            'total_sukarela' => (float) $sumSukarela,
            'total_khusus' => (float) $sumKhusus,
            'grand_total' => (float) $grandTotal,
            'cutoff_date' => ($trimmedCutoff !== '') ? $trimmedCutoff : null,
        ];

        $isBatchValid = count($allErrors) === 0 && $invalidRowsCount === 0;

        return new ImportValidationResult(
            valid: $isBatchValid,
            headerValid: true,
            totalRows: $totalRows,
            validRows: $validRowsCount,
            invalidRows: $invalidRowsCount,
            errors: $allErrors,
            rows: $resultRows,
            csvVersion: $version,
            openingBalanceSummary: $openingBalanceSummary,
        );
    }

    private function detectBatchDuplicates(array $normalizedRowsMap, array &$rowErrorsMap): void
    {
        // 1. Email duplicates in batch
        $emailsByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $email = $row['email'];
            if (is_string($email) && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailsByRow[$rowNumber] = $email;
            }
        }
        $emailCounts = array_count_values($emailsByRow);
        foreach ($emailsByRow as $rowNumber => $email) {
            if (($emailCounts[$email] ?? 0) > 1) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'email',
                    code: self::CODE_DUPLICATE_EMAIL_BATCH,
                    message: "Email '{$email}' duplikat di dalam batch impor.",
                );
            }
        }

        // 2. Identity number duplicates in batch
        $niksByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $nik = $row['identity_number'];
            if (is_string($nik) && preg_match('/^\d{16}$/', $nik)) {
                $niksByRow[$rowNumber] = $nik;
            }
        }
        $nikCounts = array_count_values($niksByRow);
        foreach ($niksByRow as $rowNumber => $nik) {
            if (($nikCounts[$nik] ?? 0) > 1) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'identity_number',
                    code: self::CODE_DUPLICATE_IDENTITY_NUMBER_BATCH,
                    message: 'Nomor identitas (NIK) duplikat di dalam batch impor.',
                );
            }
        }

        // 3. Member number duplicates in batch (if supplied)
        $memberNosByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $memberNo = $row['member_number'];
            if (is_string($memberNo) && $memberNo !== '') {
                $memberNosByRow[$rowNumber] = $memberNo;
            }
        }
        $memberNoCounts = array_count_values($memberNosByRow);
        foreach ($memberNosByRow as $rowNumber => $memberNo) {
            if (($memberNoCounts[$memberNo] ?? 0) > 1) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'member_number',
                    code: self::CODE_DUPLICATE_MEMBER_NUMBER_BATCH,
                    message: "Nomor anggota '{$memberNo}' duplikat di dalam batch impor.",
                );
            }
        }
    }

    /**
     * Detect conflicts against existing records in the database.
     *
     * @param  array<int, array<string, mixed>>  $normalizedRowsMap
     * @param  array<int, list<ImportValidationError>>  $rowErrorsMap
     */
    private function detectDatabaseConflicts(array $normalizedRowsMap, array &$rowErrorsMap): void
    {
        // 1. Email DB conflicts (against users.email and cooperative_members.email)
        $validEmailsByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $email = $row['email'];
            if (is_string($email) && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmailsByRow[$rowNumber] = $email;
            }
        }

        if ($validEmailsByRow !== []) {
            $uniqueEmails = array_values(array_unique($validEmailsByRow));

            $existingUserEmails = User::query()
                ->whereIn(DB::raw('LOWER(email)'), $uniqueEmails)
                ->pluck('email')
                ->map(fn ($e) => strtolower((string) $e))
                ->flip()
                ->all();

            $existingMemberEmails = CooperativeMember::withTrashed()
                ->whereNotNull('email')
                ->whereIn(DB::raw('LOWER(email)'), $uniqueEmails)
                ->pluck('email')
                ->map(fn ($e) => strtolower((string) $e))
                ->flip()
                ->all();

            foreach ($validEmailsByRow as $rowNumber => $email) {
                if (isset($existingUserEmails[$email]) || isset($existingMemberEmails[$email])) {
                    $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'email',
                        code: self::CODE_EMAIL_ALREADY_EXISTS,
                        message: "Email '{$email}' sudah terdaftar di sistem.",
                    );
                }
            }
        }

        // 2. Identity number DB conflicts (via PiiCryptoService blind index)
        $validNiksByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $nik = $row['identity_number'];
            if (is_string($nik) && preg_match('/^\d{16}$/', $nik)) {
                $validNiksByRow[$rowNumber] = $nik;
            }
        }

        if ($validNiksByRow !== []) {
            $crypto = $this->piiCrypto ?? app(PiiCryptoService::class);
            $nikToBidxs = [];
            $allBidxs = [];

            foreach (array_unique($validNiksByRow) as $nik) {
                $bidxs = $crypto->blindIndexesForActiveVersions('identity_number', $nik);
                $nikToBidxs[$nik] = array_values($bidxs);
                foreach ($bidxs as $bidx) {
                    $allBidxs[] = $bidx;
                }
            }

            if ($allBidxs !== []) {
                $existingBidxs = CooperativeMember::withTrashed()
                    ->whereIn('identity_number_bidx', array_unique($allBidxs))
                    ->pluck('identity_number_bidx')
                    ->flip()
                    ->all();

                foreach ($validNiksByRow as $rowNumber => $nik) {
                    $rowBidxs = $nikToBidxs[$nik] ?? [];
                    $hasConflict = false;
                    foreach ($rowBidxs as $bidx) {
                        if (isset($existingBidxs[$bidx])) {
                            $hasConflict = true;
                            break;
                        }
                    }

                    if ($hasConflict) {
                        $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                            row: $rowNumber,
                            field: 'identity_number',
                            code: self::CODE_IDENTITY_NUMBER_ALREADY_EXISTS,
                            message: 'Nomor identitas (NIK) sudah terdaftar di sistem.',
                        );
                    }
                }
            }
        }

        // 3. Member number DB conflicts (no_anggota / member_no)
        $validMemberNosByRow = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $memberNo = $row['member_number'];
            if (is_string($memberNo) && $memberNo !== '') {
                $validMemberNosByRow[$rowNumber] = $memberNo;
            }
        }

        if ($validMemberNosByRow !== []) {
            $uniqueMemberNos = array_values(array_unique($validMemberNosByRow));

            $existingMemberNos = CooperativeMember::withTrashed()
                ->where(function ($query) use ($uniqueMemberNos) {
                    $query->whereIn('no_anggota', $uniqueMemberNos)
                        ->orWhereIn('member_no', $uniqueMemberNos);
                })
                ->get(['no_anggota', 'member_no'])
                ->flatMap(fn (CooperativeMember $m) => array_filter([$m->no_anggota, $m->member_no]))
                ->map(fn ($val) => strtoupper((string) $val))
                ->flip()
                ->all();

            foreach ($validMemberNosByRow as $rowNumber => $memberNo) {
                if (isset($existingMemberNos[$memberNo])) {
                    $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                        row: $rowNumber,
                        field: 'member_number',
                        code: self::CODE_MEMBER_NUMBER_ALREADY_EXISTS,
                        message: "Nomor anggota '{$memberNo}' sudah terdaftar di sistem.",
                    );
                }
            }
        }
    }

    /**
     * Resolve employee references against the authoritative organization context.
     *
     * @param  array<int, array<string, mixed>>  $normalizedRowsMap
     * @param  array<int, list<ImportValidationError>>  $rowErrorsMap
     * @param  array<int, string>  $employeeStatusMap
     * @param  array<int, ?int>  $resolvedEmployeeIdMap
     * @param  array<string, mixed>  $context
     */
    private function resolveEmployeeReferences(
        array $normalizedRowsMap,
        array &$rowErrorsMap,
        array &$employeeStatusMap,
        array &$resolvedEmployeeIdMap,
        array $context,
    ): void {
        // Find rows that provided an employee number
        $rowsWithEmp = [];
        foreach ($normalizedRowsMap as $rowNumber => $row) {
            $empCode = $row['employee_number'];
            if (is_string($empCode) && $empCode !== '') {
                $rowsWithEmp[$rowNumber] = $empCode;
            }
        }

        if ($rowsWithEmp === []) {
            return;
        }

        // Determine organization context
        $organizationId = $context['organization_id'] ?? null;
        if ($organizationId === null && isset($context['organization'])) {
            if ($context['organization'] instanceof Organization) {
                $organizationId = $context['organization']->id;
            } elseif (is_array($context['organization']) && isset($context['organization']['id'])) {
                $organizationId = $context['organization']['id'];
            }
        }

        if ($organizationId === null || trim((string) $organizationId) === '') {
            foreach ($rowsWithEmp as $rowNumber => $empCode) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_REFERENCE_CONTEXT_UNRESOLVED,
                    message: "Konteks organisasi tidak disediakan untuk resolusi NIP '{$empCode}'.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_UNRESOLVED;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            }

            return;
        }

        $organizationIdStr = (string) $organizationId;
        $orgExists = Organization::query()->where('id', $organizationIdStr)->exists();
        if (! $orgExists) {
            foreach ($rowsWithEmp as $rowNumber => $empCode) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_REFERENCE_CONTEXT_UNRESOLVED,
                    message: "Organisasi konteks '{$organizationIdStr}' tidak ditemukan di sistem.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_UNRESOLVED;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            }

            return;
        }

        // Query employees matching employee_code strictly scoped to the organization at SQL level
        $uniqueEmpCodes = array_values(array_unique($rowsWithEmp));
        $employees = Employee::query()
            ->where('organization_id', $organizationIdStr)
            ->whereIn('employee_code', $uniqueEmpCodes)
            ->get();
        $employeesByCode = $employees->groupBy('employee_code');

        $resolvedEmployeesByRow = [];

        foreach ($rowsWithEmp as $rowNumber => $empCode) {
            $matchingEmployees = $employeesByCode->get($empCode, collect());

            if ($matchingEmployees->isEmpty()) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_UNRESOLVED_EMPLOYEE_REFERENCE,
                    message: "Pegawai dengan NIP '{$empCode}' tidak ditemukan pada organisasi yang dipilih.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_UNRESOLVED;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            } elseif ($matchingEmployees->count() > 1) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_EMPLOYEE_REFERENCE_CONFLICT,
                    message: "Ditemukan lebih dari satu pegawai dengan NIP '{$empCode}' pada organisasi yang dipilih.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_CONFLICT;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            } else {
                $resolvedEmployeesByRow[$rowNumber] = $matchingEmployees->first();
            }
        }

        if ($resolvedEmployeesByRow === []) {
            return;
        }

        // Check conflicts for resolved employees:
        // 1. Employee already linked to an existing active/non-deleted member
        $candidateEmployeeIds = array_map(fn (Employee $e) => $e->id, $resolvedEmployeesByRow);
        $alreadyLinkedEmployeeIds = CooperativeMember::query()
            ->whereNull('deleted_at')
            ->whereIn('employee_id', array_unique($candidateEmployeeIds))
            ->pluck('employee_id')
            ->flip()
            ->all();

        // 2. Batch duplicate employee reference
        $empIdCountsInBatch = array_count_values($candidateEmployeeIds);

        foreach ($resolvedEmployeesByRow as $rowNumber => $employee) {
            $empCode = $rowsWithEmp[$rowNumber];

            if (isset($alreadyLinkedEmployeeIds[$employee->id])) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_EMPLOYEE_REFERENCE_CONFLICT,
                    message: "Pegawai dengan NIP '{$empCode}' sudah terhubung ke anggota koperasi lain.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_CONFLICT;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            } elseif (($empIdCountsInBatch[$employee->id] ?? 0) > 1) {
                $rowErrorsMap[$rowNumber][] = new ImportValidationError(
                    row: $rowNumber,
                    field: 'employee_number',
                    code: self::CODE_EMPLOYEE_REFERENCE_CONFLICT,
                    message: "Pegawai dengan NIP '{$empCode}' ditautkan ke lebih dari satu baris dalam batch impor.",
                );
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_CONFLICT;
                $resolvedEmployeeIdMap[$rowNumber] = null;
            } else {
                $employeeStatusMap[$rowNumber] = self::EMPLOYEE_RESOLUTION_RESOLVED;
                $resolvedEmployeeIdMap[$rowNumber] = $employee->id;
            }
        }
    }

    /**
     * Strict calendar date check.
     */
    private function isValidCalendarDate(string $dateStr): bool
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr, $matches)) {
            return false;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        return checkdate($month, $day, $year);
    }
}
