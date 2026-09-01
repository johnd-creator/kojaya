<?php

namespace App\Services\Auth;

use App\Enums\PermissionEnum;
use App\Models\User;

class TokenAbilityResolver
{
    public function __construct(
        private readonly AbilityCutoverPolicy $cutover,
    ) {}

    /**
     * Backward-compatible coarse abilities retained during the migration
     * from cooperative:read/write to granular domain abilities.
     */
    private const LEGACY_COOPERATIVE_READ_PERMS = [
        PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
        PermissionEnum::COOPERATIVE_DUES_MANAGE->value,
        PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value,
        PermissionEnum::COOPERATIVE_LOAN_MANAGE->value,
        PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
        PermissionEnum::COOPERATIVE_POS_ACCESS->value,
        PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        PermissionEnum::COOPERATIVE_REPORT_VIEW->value,
        PermissionEnum::COOPERATIVE_POINTS_MANAGE->value,
        PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
        PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value,
        PermissionEnum::COOPERATIVE_SHU_MANAGE->value,
        PermissionEnum::COOPERATIVE_LOAN_TYPES_MANAGE->value,
        PermissionEnum::COOPERATIVE_LEDGER_VIEW->value,
        PermissionEnum::COOPERATIVE_SETTINGS_MANAGE->value,
        PermissionEnum::COOPERATIVE_MEMBER_VIEW->value,
        PermissionEnum::COOPERATIVE_LOAN_VIEW->value,
        PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value,
    ];

    private const LEGACY_COOPERATIVE_WRITE_PERMS = [
        PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
        PermissionEnum::COOPERATIVE_DUES_MANAGE->value,
        PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value,
        PermissionEnum::COOPERATIVE_LOAN_MANAGE->value,
        PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
    ];

    /**
     * @return array<int, string>
     */
    public function for(User $user, ?string $app): array
    {
        $phase = $this->cutover->phase();
        $abilities = ['profile:read'];

        if ($user->cooperativeMember) {
            $abilities[] = 'member:read';
            $abilities[] = 'member:write';
        }

        // --- Granular cooperative abilities (P1.2) ---
        $abilities = array_merge($abilities, $this->resolveCooperativeAbilities($user));

        // Legacy abilities are issued only during the explicit instrument phase.
        if ($phase->value === 'instrument') {
            if ($this->canAny($user, self::LEGACY_COOPERATIVE_READ_PERMS)) {
                $abilities[] = 'cooperative:read';
            }

            if ($this->canAny($user, self::LEGACY_COOPERATIVE_WRITE_PERMS)) {
                $abilities[] = 'cooperative:write';
            }
        }

        if ($user->employee || $this->canAny($user, [
            PermissionEnum::ESS_PORTAL_ACCESS->value,
            PermissionEnum::EMPLOYEE_VIEW_ALL->value,
            PermissionEnum::EMPLOYEE_VIEW_UNIT->value,
        ])) {
            $abilities[] = 'ess:read';
            $abilities[] = 'ess:write';
            $abilities[] = 'attendance:read';
            $abilities[] = 'attendance:write';
            $abilities[] = 'payroll:read';
        }

        if ($this->canAny($user, [
            PermissionEnum::EMPLOYEE_VIEW_ALL->value,
            PermissionEnum::EMPLOYEE_VIEW_UNIT->value,
        ])) {
            $abilities[] = 'employee-documents:read';
        }

        if ($user->can(PermissionEnum::EMPLOYEE_EDIT->value)) {
            $abilities[] = 'employee-documents:write';
        }

        if ($this->canAny($user, [
            PermissionEnum::WORK_ORDER_VIEW_ALL->value,
            PermissionEnum::WORK_ORDER_VIEW_UNIT->value,
            PermissionEnum::WORK_ORDER_MANAGE->value,
        ])) {
            $abilities[] = 'work-orders:read';
            $abilities[] = 'work-orders:write';
        }

        if ($user->can(PermissionEnum::WORK_ORDER_MANAGE->value)) {
            $abilities[] = 'work-orders:review';
        }

        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_REPORT_VIEW->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::POS_REPORTS_VIEW->value,
            PermissionEnum::REPORTS_VIEW->value,
        ])) {
            $abilities[] = 'reports:read';
        }

        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_POS_ACCESS->value,
            PermissionEnum::POS_PRODUCTS_MANAGE->value,
            PermissionEnum::POS_CATEGORIES_MANAGE->value,
        ])) {
            $abilities[] = 'pos:read';
            $abilities[] = 'pos:write';
        }

        $abilities = array_values(array_unique($abilities));

        return match ($app) {
            'member' => $this->only($abilities, ['profile:read', 'member:read', 'member:write']),
            'ess' => $this->only($abilities, ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read']),
            'technician' => $this->only($abilities, ['profile:read', 'work-orders:read', 'work-orders:write', 'work-orders:review']),
            'admin' => $this->onlyAdmin($abilities),
            default => $abilities,
        };
    }

    /**
     * Resolve granular, domain-specific cooperative abilities from permissions.
     *
     * @return array<int, string>
     */
    private function resolveCooperativeAbilities(User $user): array
    {
        $abilities = [];

        // Member domain
        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_MEMBER_VIEW->value,
            PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        ])) {
            $abilities[] = 'cooperative.member.read';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)) {
            $abilities[] = 'cooperative.member.write';
        }

        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_MEMBER_VALIDATE->value,
            PermissionEnum::COOPERATIVE_MEMBER_VERIFY->value,
        ])) {
            $abilities[] = 'cooperative.member.verify';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value)) {
            $abilities[] = 'cooperative.member.approve';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_MEMBER_EXPORT->value)) {
            $abilities[] = 'cooperative.member.export';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value)) {
            $abilities[] = 'cooperative.resignation.review';
        }

        // Dues domain
        if ($user->can(PermissionEnum::COOPERATIVE_DUES_MANAGE->value)) {
            $abilities[] = 'cooperative.dues.read';
            $abilities[] = 'cooperative.dues.write';
        }

        // Payment domain
        if ($user->can(PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value)) {
            $abilities[] = 'cooperative.payment.read';
            $abilities[] = 'cooperative.payment.record';
        }

        // Loan domain
        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_LOAN_VIEW->value,
            PermissionEnum::COOPERATIVE_LOAN_MANAGE->value,
            PermissionEnum::COOPERATIVE_LOAN_REVIEW->value,
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
        ])) {
            $abilities[] = 'cooperative.loan.read';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_LOAN_MANAGE->value)) {
            $abilities[] = 'cooperative.loan.write';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_LOAN_REVIEW->value)) {
            $abilities[] = 'cooperative.loan.review';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_LOAN_APPROVE->value)) {
            $abilities[] = 'cooperative.loan.approve';
        }

        // Ledger domain
        if ($user->can(PermissionEnum::COOPERATIVE_LEDGER_VIEW->value)) {
            $abilities[] = 'cooperative.ledger.read';
        }

        if ($user->can(PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value)) {
            $abilities[] = 'cooperative.ledger.write';
        }

        // Report domain
        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_REPORT_VIEW->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::POS_REPORTS_VIEW->value,
        ])) {
            $abilities[] = 'cooperative.report.read';
        }

        // POS domain
        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_POS_ACCESS->value,
            PermissionEnum::POS_PRODUCTS_MANAGE->value,
            PermissionEnum::POS_CATEGORIES_MANAGE->value,
        ])) {
            $abilities[] = 'cooperative.pos.read';
            $abilities[] = 'cooperative.pos.write';
        }

        // Settings domain
        if ($user->can(PermissionEnum::COOPERATIVE_SETTINGS_MANAGE->value)) {
            $abilities[] = 'cooperative.settings.write';
        }

        return $abilities;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $abilities
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    private function only(array $abilities, array $allowed): array
    {
        return array_values(array_intersect($abilities, $allowed));
    }

    /**
     * Admin tokens may carry documented admin domains, but never member, ESS,
     * technician, or wildcard abilities as an accidental combined profile.
     *
     * @param  array<int, string>  $abilities
     * @return array<int, string>
     */
    private function onlyAdmin(array $abilities): array
    {
        return array_values(array_filter($abilities, static function (string $ability): bool {
            return $ability === 'profile:read'
                || str_starts_with($ability, 'cooperative.')
                || str_starts_with($ability, 'cooperative:')
                || str_starts_with($ability, 'pos:')
                || str_starts_with($ability, 'reports:')
                || str_starts_with($ability, 'employee-documents:');
        }));
    }
}
