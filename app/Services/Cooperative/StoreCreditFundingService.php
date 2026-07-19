<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreFundingMethod;
use App\Enums\MemberStoreFundingStatus;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreFundingRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreCreditFundingService
{
    private const string PROOF_DISK = 'local';

    private const string PROOF_DIRECTORY = 'store-credit-proofs';

    public function __construct(
        private StoreCreditLedgerService $ledger,
        private AuditLogService $auditLog,
    ) {}

    public function submitCashFunding(
        MemberStoreAccount $account,
        int $amount,
        User $cashier,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): MemberStoreFundingRequest {
        $key = $this->resolveFundingKey($account, $idempotencyKey);

        $replayed = $this->findExistingFunding($key);
        if ($replayed !== null) {
            return $replayed;
        }

        return DB::transaction(function () use ($account, $amount, $cashier, $referenceNo, $notes, $key): MemberStoreFundingRequest {
            $replayed = $this->findExistingFundingForUpdate($key);
            if ($replayed !== null) {
                return $replayed;
            }

            $funding = $this->createFundingRequest($account, MemberStoreFundingMethod::Cash, $amount, $cashier, $referenceNo, null, $notes, $key);

            $entry = $this->ledger->postCashFunding($account, $funding, $cashier);

            $funding->status = MemberStoreFundingStatus::Approved->value;
            $funding->reviewed_by = $cashier->id;
            $funding->reviewed_at = now();
            $funding->posted_ledger_entry_id = $entry->id;
            $funding->save();

            return $funding->refresh();
        });
    }

    public function submitTransferFunding(
        MemberStoreAccount $account,
        int $amount,
        User $submitter,
        ?string $bankReference = null,
        ?UploadedFile $proof = null,
        ?string $idempotencyKey = null,
    ): MemberStoreFundingRequest {
        $key = $this->resolveFundingKey($account, $idempotencyKey);

        $replayed = $this->findExistingFunding($key);
        if ($replayed !== null) {
            return $replayed;
        }

        return DB::transaction(function () use ($account, $amount, $submitter, $bankReference, $proof, $key): MemberStoreFundingRequest {
            $replayed = $this->findExistingFundingForUpdate($key);
            if ($replayed !== null) {
                return $replayed;
            }

            $proofPath = null;

            if ($proof !== null) {
                $proofPath = $this->storeProof($proof);
            }

            return $this->createFundingRequest(
                account: $account,
                method: MemberStoreFundingMethod::Transfer,
                amount: $amount,
                submitter: $submitter,
                bankReference: $bankReference,
                proofPath: $proofPath,
                idempotencyKey: $key,
            );
        });
    }

    public function approveTransfer(MemberStoreFundingRequest $funding, User $reviewer): MemberStoreFundingRequest
    {
        if ($funding->method !== MemberStoreFundingMethod::Transfer) {
            throw ValidationException::withMessages([
                'method' => 'Hanya setoran transfer yang memerlukan persetujuan.',
            ]);
        }

        if ($funding->status !== MemberStoreFundingStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Setoran yang sudah diproses tidak dapat diproses ulang.',
            ]);
        }

        if ($funding->submitted_by !== null && $funding->submitted_by === $reviewer->id) {
            throw ValidationException::withMessages([
                'reviewer' => 'Reviewer tidak boleh sama dengan pemohon (maker-checker).',
            ]);
        }

        return DB::transaction(function () use ($funding, $reviewer): MemberStoreFundingRequest {
            $locked = MemberStoreFundingRequest::query()->lockForUpdate()->findOrFail($funding->id);

            if ($locked->status !== MemberStoreFundingStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Setoran yang sudah diproses tidak dapat diproses ulang.',
                ]);
            }

            $entry = $this->ledger->postTransferFunding($locked->account, $locked, $reviewer);

            $locked->status = MemberStoreFundingStatus::Approved->value;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->posted_ledger_entry_id = $entry->id;
            $locked->save();

            $this->audit('member_store_credit.funding.approved', $locked, $reviewer);

            return $locked->refresh();
        });
    }

    public function rejectTransfer(MemberStoreFundingRequest $funding, User $reviewer, ?string $reason = null): MemberStoreFundingRequest
    {
        if ($funding->status !== MemberStoreFundingStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Setoran yang sudah diproses tidak dapat diproses ulang.',
            ]);
        }

        return DB::transaction(function () use ($funding, $reviewer, $reason): MemberStoreFundingRequest {
            $locked = MemberStoreFundingRequest::query()->lockForUpdate()->findOrFail($funding->id);

            if ($locked->status !== MemberStoreFundingStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Setoran yang sudah diproses tidak dapat diproses ulang.',
                ]);
            }

            $locked->status = MemberStoreFundingStatus::Rejected->value;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->rejection_reason = $reason;
            $locked->save();

            $this->audit('member_store_credit.funding.rejected', $locked, $reviewer, $reason);

            return $locked->refresh();
        });
    }

    public function cancel(MemberStoreFundingRequest $funding, User $user): MemberStoreFundingRequest
    {
        if ($funding->status !== MemberStoreFundingStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Hanya setoran pending yang dapat dibatalkan.',
            ]);
        }

        $funding->status = MemberStoreFundingStatus::Cancelled->value;
        $funding->save();

        return $funding->refresh();
    }

    public function streamProof(MemberStoreFundingRequest $funding): ?string
    {
        $path = $funding->getRawOriginal('proof_path');

        if ($path === null || ! Storage::disk(self::PROOF_DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::PROOF_DISK)->path($path);
    }

    /**
     * Stream the transfer proof from the private disk as a download response.
     * Returns null when no proof is stored, so callers can emit a safe 404.
     * The storage path is never exposed to the client.
     */
    public function downloadProofResponse(MemberStoreFundingRequest $funding): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = $funding->getRawOriginal('proof_path');

        if ($path === null || ! Storage::disk(self::PROOF_DISK)->exists($path)) {
            return null;
        }

        $filename = 'bukti-setoran-'.$funding->id.'.'.$this->proofExtension($path);

        return Storage::disk(self::PROOF_DISK)->download($path, $filename, [
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    private function proofExtension(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== '' ? $extension : 'bin';
    }

    private function createFundingRequest(
        MemberStoreAccount $account,
        MemberStoreFundingMethod $method,
        int $amount,
        User $submitter,
        ?string $bankReference = null,
        ?string $proofPath = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): MemberStoreFundingRequest {
        try {
            return MemberStoreFundingRequest::create([
                'account_id' => $account->id,
                'organization_id' => $account->organization_id,
                'method' => $method->value,
                'amount' => $amount,
                'status' => MemberStoreFundingStatus::Pending->value,
                'proof_path' => $proofPath,
                'bank_reference' => $bankReference,
                'submitted_by' => $submitter->id,
                'idempotency_key' => $idempotencyKey ?? 'funding:'.$account->id.':'.Str::uuid(),
                'rejection_reason' => null,
            ]);
        } catch (QueryException $exception) {
            // Concurrent duplicate with the same idempotency key — return the winner.
            $existing = $this->findExistingFunding($idempotencyKey);

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    /**
     * Build a stable funding idempotency key scoped to the account when the
     * caller does not supply one, so retries never silently create duplicates.
     */
    private function resolveFundingKey(MemberStoreAccount $account, ?string $idempotencyKey): string
    {
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            return 'funding:'.$account->id.':'.$idempotencyKey;
        }

        return 'funding:'.$account->id.':'.Str::uuid();
    }

    private function findExistingFunding(?string $key): ?MemberStoreFundingRequest
    {
        if ($key === null || $key === '') {
            return null;
        }

        return MemberStoreFundingRequest::query()
            ->where('idempotency_key', $key)
            ->first();
    }

    private function findExistingFundingForUpdate(?string $key): ?MemberStoreFundingRequest
    {
        if ($key === null || $key === '') {
            return null;
        }

        return MemberStoreFundingRequest::query()
            ->where('idempotency_key', $key)
            ->lockForUpdate()
            ->first();
    }

    private function storeProof(UploadedFile $file): string
    {
        return $file->store(self::PROOF_DIRECTORY, self::PROOF_DISK);
    }

    private function audit(string $action, MemberStoreFundingRequest $funding, User $actor, ?string $reason = null): void
    {
        $this->auditLog->log(
            action: $action,
            module: StoreCreditLedgerService::MODULE,
            subject: $funding,
            changes: [
                'new' => [
                    'amount' => (int) $funding->amount,
                    'method' => $funding->method->value,
                    'status' => $funding->status->value,
                ],
                'reason' => $reason,
            ],
            context: AuditContext::forActor($actor),
        );
    }
}
