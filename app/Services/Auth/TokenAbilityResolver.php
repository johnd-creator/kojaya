<?php

namespace App\Services\Auth;

use App\Enums\PermissionEnum;
use App\Models\User;

class TokenAbilityResolver
{
    /**
     * @return array<int, string>
     */
    public function for(User $user, ?string $app): array
    {
        if ($this->hasAdminTokenAccess($user)) {
            return ['*'];
        }

        $abilities = ['profile:read'];

        if ($user->cooperativeMember) {
            $abilities[] = 'member:read';
            $abilities[] = 'member:write';
        }

        if ($this->canAny($user, [
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
        ])) {
            $abilities[] = 'cooperative:read';
        }

        if ($this->canAny($user, [
            PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
            PermissionEnum::COOPERATIVE_DUES_MANAGE->value,
            PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value,
            PermissionEnum::COOPERATIVE_LOAN_MANAGE->value,
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
        ])) {
            $abilities[] = 'cooperative:write';
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
            default => $abilities,
        };
    }

    private function hasAdminTokenAccess(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::ORGANIZATIONS_MANAGE->value,
            PermissionEnum::USERS_MANAGE->value,
            PermissionEnum::ROLES_MANAGE->value,
        ]);
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
}
