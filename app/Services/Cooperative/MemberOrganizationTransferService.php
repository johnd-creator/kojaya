<?php

namespace App\Services\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\Loan;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Authorization\OrganizationScopeService;
use App\Support\AuditContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class MemberOrganizationTransferService
{
    public function __construct(
        private readonly OrganizationScopeService $scope,
        private readonly AuditLogService $audit,
        private readonly DatabaseManager $database,
    ) {}

    public function transfer(
        User $actor,
        CooperativeMember $member,
        Organization $targetOrganization,
        string $reason,
        ?AuditContext $context = null,
    ): CooperativeMember {
        $this->assertCanTransfer($actor, $member);

        $sourceOrganizationId = (string) $member->organization_id;
        $targetOrganizationId = (string) $targetOrganization->getKey();

        if ($sourceOrganizationId === $targetOrganizationId) {
            throw ValidationException::withMessages([
                'target_organization_id' => 'Anggota sudah berada di organisasi tujuan.',
            ]);
        }

        if (! $targetOrganization->is_active) {
            throw ValidationException::withMessages([
                'target_organization_id' => 'Organisasi tujuan sedang tidak aktif.',
            ]);
        }

        if ($member->employee_id !== null) {
            throw ValidationException::withMessages([
                'target_organization_id' => 'Anggota yang tertaut ke data karyawan harus dipindahkan melalui workflow transfer karyawan terlebih dahulu.',
            ]);
        }

        $context ??= AuditContext::forActor($actor);
        $reason = trim($reason);

        return $this->database->transaction(function () use (

            $member,
            $targetOrganization,
            $sourceOrganizationId,
            $targetOrganizationId,
            $reason,
            $context,
        ): CooperativeMember {
            $lockedMember = CooperativeMember::query()->lockForUpdate()->findOrFail($member->getKey());
            $linkedUser = $lockedMember->user_id !== null
                ? User::query()->lockForUpdate()->find($lockedMember->user_id)
                : null;
            $oldUserOrganizationId = $linkedUser?->organization_id;

            $this->assertTransferState($lockedMember, $sourceOrganizationId, $targetOrganizationId);

            $linkedStoreAccount = MemberStoreAccount::query()
                ->where('cooperative_member_id', $lockedMember->getKey())
                ->lockForUpdate()
                ->first();

            if (MemberStoreAccount::query()
                ->where('cooperative_member_id', $lockedMember->getKey())
                ->where('organization_id', $targetOrganizationId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'target_organization_id' => 'Anggota sudah memiliki akun toko di organisasi tujuan.',
                ]);
            }

            if ($linkedStoreAccount !== null && MemberStoreDelegate::query()
                ->where('account_id', $linkedStoreAccount->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'target_organization_id' => 'Lepas delegate akun toko terlebih dahulu sebelum memindahkan anggota.',
                ]);
            }

            $lockedMember->forceFill(['organization_id' => $targetOrganizationId])->save();

            if ($linkedUser !== null) {
                $linkedUser->forceFill(['organization_id' => $targetOrganizationId])->save();
            }

            CooperativeLedgerEntry::query()
                ->where('cooperative_member_id', $lockedMember->getKey())
                ->update(['organization_id' => $targetOrganizationId]);

            CooperativeMemberOpeningBalanceBatch::query()
                ->where('cooperative_member_id', $lockedMember->getKey())
                ->update(['organization_id' => $targetOrganizationId]);

            Loan::query()
                ->where('cooperative_member_id', $lockedMember->getKey())
                ->update(['organization_id' => $targetOrganizationId]);

            if ($linkedStoreAccount !== null) {
                $linkedStoreAccount->forceFill(['organization_id' => $targetOrganizationId])->save();

                // Store ledger entries are immutable in normal operations. A
                // controlled organization transfer is the only workflow that
                // relocates their scope without changing their accounting data.
                MemberStoreLedgerEntry::query()
                    ->where('account_id', $linkedStoreAccount->getKey())
                    ->update(['organization_id' => $targetOrganizationId]);

                MemberStoreFundingRequest::query()
                    ->where('account_id', $linkedStoreAccount->getKey())
                    ->update(['organization_id' => $targetOrganizationId]);

                MemberStoreDelegate::query()
                    ->where('account_id', $linkedStoreAccount->getKey())
                    ->update(['organization_id' => $targetOrganizationId]);
            }

            $this->audit->log('cooperative.member.organization.transferred', 'cooperative.member', $lockedMember, [
                'old' => [
                    'organization_id' => $sourceOrganizationId,
                    'user_organization_id' => $oldUserOrganizationId,
                ],
                'new' => [
                    'organization_id' => $targetOrganizationId,
                    'organization_name' => $targetOrganization->name,
                    'user_id' => $linkedUser?->getKey(),
                    'user_organization_id' => $linkedUser?->organization_id,
                    'reason' => $reason,
                ],
                'reason' => 'Anggota dipindahkan melalui workflow organisasi terpusat.',
            ], $context);

            return $lockedMember->refresh();
        });
    }

    private function assertCanTransfer(User $actor, CooperativeMember $member): void
    {
        if (! $actor->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)
            || ! $actor->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value)) {
            throw new AuthorizationException('Pemindahan anggota antar organisasi hanya dapat dilakukan oleh pengurus dengan akses seluruh organisasi.');
        }

        $this->scope->assertVisible($actor, $member);
    }

    private function assertTransferState(
        CooperativeMember $member,
        string $sourceOrganizationId,
        string $targetOrganizationId,
    ): void {
        if ((string) $member->organization_id !== $sourceOrganizationId) {
            throw ValidationException::withMessages([
                'target_organization_id' => 'Data anggota berubah saat proses dimulai. Silakan muat ulang halaman.',
            ]);
        }

        if ($sourceOrganizationId === $targetOrganizationId) {
            throw ValidationException::withMessages([
                'target_organization_id' => 'Anggota sudah berada di organisasi tujuan.',
            ]);
        }
    }
}
