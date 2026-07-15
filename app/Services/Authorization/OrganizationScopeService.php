<?php

namespace App\Services\Authorization;

use App\Contracts\OrganizationScopedModel;
use App\Enums\OrganizationVisibilityState;
use App\Enums\PermissionEnum;
use App\Exceptions\OrganizationScopeException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Budget;
use App\Models\Client;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GoodsReceiveNote;
use App\Models\Invoice;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanRestructure;
use App\Models\MemberResignationRequest;
use App\Models\PettyCashAccount;
use App\Models\PosMemberCreditPayment;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;
use App\Models\RewardRedemption;
use App\Models\SalaryStructure;
use App\Models\SavingsWithdrawal;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Support\OrganizationVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;

class OrganizationScopeService
{
    /**
     * @var array<class-string<Model>, string>
     */
    private const REGISTERED_PATHS = [
        CooperativeMember::class => 'organization_id',
        CooperativeDuesInvoice::class => 'member.organization_id',
        CooperativePayment::class => 'member.organization_id',
        CooperativeLedgerEntry::class => 'organization_id',
        Attendance::class => 'organization_id',
        AttendanceCorrection::class => 'organization_id',
        Budget::class => 'organization_id',
        Client::class => 'organization_id',
        Department::class => 'organization_id',
        GoodsReceiveNote::class => 'organization_id',
        Invoice::class => 'organization_id',
        Loan::class => 'organization_id',
        LoanInstallment::class => 'loan.organization_id',
        LoanPayment::class => 'loan.organization_id',
        LoanRestructure::class => 'loan.organization_id',
        MemberResignationRequest::class => 'member.organization_id',
        PettyCashAccount::class => 'organization_id',
        SavingsWithdrawal::class => 'member.organization_id',
        RewardRedemption::class => 'member.organization_id',
        PosMemberCreditPayment::class => 'member.organization_id',
        Project::class => 'organization_id',
        PurchaseOrder::class => 'organization_id',
        PurchaseRequest::class => 'organization_id',
        Reimbursement::class => 'organization_id',
        SalaryStructure::class => 'organization_id',
        SparePart::class => 'organization_id',
        Vendor::class => 'organization_id',
        Warehouse::class => 'organization_id',
        Employee::class => 'organization_id',
        User::class => 'organization_id',
    ];

    /**
     * Global visibility is permission-based for every registered model.
     *
     * @var array<class-string<Model>, string>
     */
    private const GLOBAL_PERMISSIONS = [
        Attendance::class => 'view_attendance_all',
        AttendanceCorrection::class => 'view_attendance_all',
        Budget::class => 'view_budget_all',
        Client::class => 'manage_clients',
        Department::class => 'manage_departments',
        Employee::class => 'view_employee_all',
        GoodsReceiveNote::class => 'view_grn_all',
        Invoice::class => 'view_invoice_all',
        PettyCashAccount::class => 'manage_petty_cash',
        Project::class => 'view_project_all',
        PurchaseOrder::class => 'view_po_all',
        PurchaseRequest::class => 'view_pr_all',
        Reimbursement::class => 'manage_reimbursement',
        SalaryStructure::class => 'manage_salary_structures',
        SparePart::class => 'manage_spare_parts',
        Vendor::class => 'manage_vendors',
        Warehouse::class => 'manage_warehouses',
    ];

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $visibility = $this->visibilityFor($user, $this->globalPermissionFor($query->getModel()));

        if ($visibility->state === OrganizationVisibilityState::GLOBAL) {
            return $query;
        }

        $model = $query->getModel();
        $path = $this->pathFor($model);
        $segments = explode('.', $path);
        $organizationColumn = array_pop($segments);

        if ($segments === []) {
            return $query->where($model->qualifyColumn($organizationColumn), $visibility->organizationId);
        }

        return $query->whereHas(implode('.', $segments), function (Builder $relatedQuery) use ($organizationColumn, $visibility): void {
            $relatedQuery->where($organizationColumn, $visibility->organizationId);
        });
    }

    public function assertUserHasOrganizationOrGlobal(User $user): void
    {
        if ($this->visibilityFor($user)->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }
    }

    public function visibilityFor(User $user, ?string $globalPermission = null): OrganizationVisibility
    {
        if ($user->can($globalPermission ?? PermissionEnum::COOPERATIVE_VIEW_ALL->value)) {
            return OrganizationVisibility::global();
        }

        if ($user->organization_id === null || $user->organization_id === '') {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }

        return OrganizationVisibility::organization((string) $user->organization_id);
    }

    public function organizationIdForModel(Model $model): string|int|null
    {
        $path = $this->pathFor($model);
        $current = $model;

        foreach (explode('.', $path) as $segment) {
            if ($current instanceof Model && $current->hasAttribute($segment)) {
                $value = $current->getAttribute($segment);
            } else {
                try {
                    $value = $current->getRelationValue($segment);
                } catch (RelationNotFoundException) {
                    throw new OrganizationScopeException("Organization scope path [{$path}] is broken for [".get_class($model).'].');
                }
            }

            if ($value instanceof Model) {
                $current = $value;

                continue;
            }

            if ($value === null) {
                throw new OrganizationScopeException("Organization scope path [{$path}] resolved to null for [".get_class($model).'].');
            }

            return $value;
        }

        throw new OrganizationScopeException("Organization scope path [{$path}] did not resolve an organization id for [".get_class($model).'].');
    }

    public function assertVisible(User $user, Model $model): void
    {
        $visibility = $this->visibilityFor($user, $this->globalPermissionFor($model));

        if ($visibility->state === OrganizationVisibilityState::GLOBAL) {
            return;
        }

        $organizationId = $this->organizationIdForModel($model);

        if ((string) $organizationId !== (string) $visibility->organizationId) {
            throw new AuthorizationException('The resource is outside the user organization.');
        }
    }

    public function pathFor(Model $model): string
    {
        $class = get_class($model);

        if ($model instanceof OrganizationScopedModel) {
            return $model->organizationScopePath();
        }

        if (isset(self::REGISTERED_PATHS[$class])) {
            return self::REGISTERED_PATHS[$class];
        }

        throw new OrganizationScopeException("Model [{$class}] has no explicit organization scope contract.");
    }

    private function globalPermissionFor(Model $model): string
    {
        return self::GLOBAL_PERMISSIONS[get_class($model)]
            ?? PermissionEnum::COOPERATIVE_VIEW_ALL->value;
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function registeredPaths(): array
    {
        return self::REGISTERED_PATHS;
    }
}
