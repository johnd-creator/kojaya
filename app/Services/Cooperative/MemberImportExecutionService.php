<?php

declare(strict_types=1);

namespace App\Services\Cooperative;

use App\Exceptions\MemberImportExecutionException;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MemberImportExecutionService
{
    /**
     * Optional hook for injecting test failures during batch insertion
     * to verify database transaction atomicity and rollback.
     */
    private ?Closure $afterRowInsertHook = null;

    public function __construct(
        private readonly MemberImportValidator $validator,
        private readonly MemberNumberGenerator $numberGenerator,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Register a test failure injection hook.
     */
    public function setAfterRowInsertHook(?Closure $hook): void
    {
        $this->afterRowInsertHook = $hook;
    }

    /**
     * Execute canonical member import in an atomic, all-or-nothing database transaction.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(
        string $filePath,
        string $organizationId,
        string $importDate,
        string $fileSha256,
        ?AuditContext $auditContext = null,
        array $options = [],
    ): MemberImportExecutionResult {
        // 1. DEV Execution Gate Check
        if (! config('cooperative.member_import_execution_enabled', false)) {
            throw new MemberImportExecutionException(
                'IMPORT_EXECUTION_DISABLED',
                'Eksekusi impor anggota saat ini dinonaktifkan.',
            );
        }

        // 2. Preflight Validation (Validation A — Fast fail outside transaction)
        $preflight = $this->validator->validateFile($filePath, [
            'organization_id' => $organizationId,
            'import_date' => $importDate,
        ]);

        if (! $this->isBatchFullyPersistable($preflight)) {
            throw new MemberImportExecutionException(
                'IMPORT_REVALIDATION_FAILED',
                'Validasi pra-eksekusi gagal: berkas CSV tidak memenuhi kriteria kelayakan impor.',
                errors: $preflight->errors,
            );
        }

        // 3. Atomic Database Transaction Boundary
        return DB::transaction(function () use ($filePath, $organizationId, $importDate, $fileSha256, $auditContext): MemberImportExecutionResult {
            // Concurrency protection: Acquire PostgreSQL advisory lock
            if (DB::getDriverName() === 'pgsql') {
                $lockKey = crc32('cooperative_members_import_lock');
                DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);
            }

            // 4. Final Commit-Time Revalidation (Validation B — Authoritative inside transaction)
            $finalValidation = $this->validator->validateFile($filePath, [
                'organization_id' => $organizationId,
                'import_date' => $importDate,
            ]);

            if (! $this->isBatchFullyPersistable($finalValidation)) {
                throw new MemberImportExecutionException(
                    'IMPORT_CONFLICT',
                    'Eksekusi impor dibatalkan karena terjadi perubahan data atau konflik sejak pratinjau.',
                    errors: $finalValidation->errors,
                );
            }

            // 5. Member Number Allocation & Reservation
            $generatedMemberNumbers = [];
            $suppliedMemberNumbers = [];

            $existingCandidates = CooperativeMember::query()
                ->withTrashed()
                ->where('no_anggota', 'like', 'KOP-%')
                ->pluck('no_anggota');

            $currentMax = $existingCandidates
                ->filter(fn (string $value): bool => (bool) preg_match('/^KOP-\d+$/', $value))
                ->map(fn (string $value): int => (int) substr($value, 4))
                ->max() ?? 0;

            // Collect all supplied member numbers to avoid collision
            $batchSupplied = [];
            foreach ($finalValidation->rows as $row) {
                if (! $row->memberNumberGenerationRequired && is_string($row->normalizedData['member_number'])) {
                    $batchSupplied[] = $row->normalizedData['member_number'];
                }
            }

            // Map each row to its final member number
            $allocatedNumbersByRow = [];
            foreach ($finalValidation->rows as $row) {
                if ($row->memberNumberGenerationRequired) {
                    do {
                        $currentMax++;
                        $candidate = 'KOP-'.str_pad((string) $currentMax, 3, '0', STR_PAD_LEFT);
                    } while (in_array($candidate, $batchSupplied, true));

                    $allocatedNumbersByRow[$row->rowNumber] = $candidate;
                    $generatedMemberNumbers[] = $candidate;
                } else {
                    $supplied = (string) $row->normalizedData['member_number'];
                    $allocatedNumbersByRow[$row->rowNumber] = $supplied;
                    $suppliedMemberNumbers[] = $supplied;
                }
            }

            // 6. Persistence: Insert all members as PENDING/PENDING
            $importId = (string) Str::uuid();
            $importedCount = 0;

            foreach ($finalValidation->rows as $index => $row) {
                $memberNumber = $allocatedNumbersByRow[$row->rowNumber];

                $member = CooperativeMember::query()->create([
                    'organization_id' => $organizationId,
                    'employee_id' => $row->resolvedEmployeeId,
                    'user_id' => null,
                    'no_anggota' => $memberNumber,
                    'member_no' => $memberNumber,
                    'name' => (string) $row->normalizedData['full_name'],
                    'nama_anggota' => (string) $row->normalizedData['full_name'],
                    'email' => (string) $row->normalizedData['email'],
                    'phone' => (string) $row->normalizedData['phone_number'],
                    'no_telp' => (string) $row->normalizedData['phone_number'],
                    'identity_number' => (string) $row->normalizedData['identity_number'],
                    'jenis_kelamin' => (string) $row->normalizedData['gender'],
                    'kategori' => (string) $row->normalizedData['company_code'],
                    'address' => (string) $row->normalizedData['address'],
                    'jenis_anggota' => (string) $row->normalizedData['membership_type'],
                    'tanggal_aktif' => (string) $row->normalizedData['join_date'],
                    'joined_at' => (string) $row->normalizedData['join_date'],
                    'notes' => $row->normalizedData['notes'],
                    'status' => CooperativeMember::VALIDATION_PENDING,
                    'validation_status' => CooperativeMember::VALIDATION_PENDING,
                ]);

                $importedCount++;

                // Trigger test failure injection hook if registered
                if ($this->afterRowInsertHook !== null) {
                    ($this->afterRowInsertHook)($member, $index + 1);
                }
            }

            // 7. Mandatory Batch Audit Log (inside transaction)
            try {
                $this->auditLogService->log(
                    action: 'member.import.completed',
                    module: 'cooperative',
                    subject: Organization::query()->find($organizationId),
                    changes: [
                        'new' => [
                            'import_id' => $importId,
                            'organization_id' => $organizationId,
                            'import_date' => $importDate,
                            'file_sha256' => $fileSha256,
                            'total_rows' => count($finalValidation->rows),
                            'imported_count' => $importedCount,
                            'generated_member_number_count' => count($generatedMemberNumbers),
                            'supplied_member_number_count' => count($suppliedMemberNumbers),
                        ],
                        'reason' => 'Batch member onboarding DEV import completed',
                    ],
                    context: $auditContext,
                );
            } catch (Throwable $e) {
                throw new MemberImportExecutionException(
                    'IMPORT_AUDIT_FAILED',
                    'Gagal mencatat log audit impor: '.$e->getMessage(),
                    previous: $e,
                );
            }

            return new MemberImportExecutionResult(
                importId: $importId,
                organizationId: $organizationId,
                importDate: $importDate,
                totalRows: count($finalValidation->rows),
                importedCount: $importedCount,
                generatedMemberNumbers: $generatedMemberNumbers,
                suppliedMemberNumbers: $suppliedMemberNumbers,
                status: 'COMPLETED',
            );
        });
    }

    /**
     * Verify whether a validation result is 100% persistable without any invalid
     * or manual review rows.
     */
    public function isBatchFullyPersistable(ImportValidationResult $result): bool
    {
        if (! $result->valid || ! $result->headerValid || $result->totalRows === 0 || $result->invalidRows > 0) {
            return false;
        }

        if ($result->requiresManualReview()) {
            return false;
        }

        foreach ($result->rows as $row) {
            if (! $row->valid || ! $row->persistable || $row->manualReviewRequired) {
                return false;
            }

            // Unresolved employee references are strictly forbidden
            if (
                $row->employeeResolutionStatus !== 'NOT_PROVIDED'
                && $row->employeeResolutionStatus !== 'RESOLVED'
            ) {
                return false;
            }
        }

        return true;
    }
}
