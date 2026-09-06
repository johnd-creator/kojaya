<?php

namespace App\Services\Authorization;

use App\Contracts\OrganizationScopedModel;
use App\Enums\OrganizationVisibilityState;
use App\Enums\PermissionEnum;
use App\Exceptions\OrganizationScopeException;
use App\Models\Asset;
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
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PettyCashAccount;
use App\Models\PosMemberCreditPayment;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\SalaryStructure;
use App\Models\SavingsWithdrawal;
use App\Models\SparePart;
use App\Models\ThrEntitlement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Support\OrganizationVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        Asset::class => 'organization_id',
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
        Payroll::class => 'organization_id',
        SavingsWithdrawal::class => 'member.organization_id',
        RewardRedemption::class => 'member.organization_id',
        PosMemberCreditPayment::class => 'member.organization_id',
        Project::class => 'organization_id',
        PurchaseOrder::class => 'organization_id',
        PurchaseRequest::class => 'organization_id',
        Reimbursement::class => 'organization_id',
        SalaryStructure::class => 'organization_id',
        SparePart::class => 'organization_id',
        ThrEntitlement::class => 'organization_id',
        Vendor::class => 'organization_id',
        Warehouse::class => 'organization_id',
        WorkOrder::class => 'organization_id',
        Employee::class => 'organization_id',
        User::class => 'organization_id',
        MemberStoreAccount::class => 'organization_id',
        MemberStoreLedgerEntry::class => 'organization_id',
        MemberStoreFundingRequest::class => 'organization_id',
        MemberStoreDelegate::class => 'organization_id',
    ];

    /**
     * Global visibility is permission-based for every registered model.
     *
     * @var array<class-string<Model>, string>
     */
    private const GLOBAL_PERMISSIONS = [
        CooperativeMember::class => 'view_cooperative_all',
        CooperativeDuesInvoice::class => 'view_cooperative_all',
        CooperativePayment::class => 'view_cooperative_all',
        CooperativeLedgerEntry::class => 'view_cooperative_all',
        Loan::class => 'view_cooperative_all',
        LoanInstallment::class => 'view_cooperative_all',
        LoanPayment::class => 'view_cooperative_all',
        LoanRestructure::class => 'view_cooperative_all',
        MemberResignationRequest::class => 'view_cooperative_all',
        SavingsWithdrawal::class => 'view_cooperative_all',
        RewardRedemption::class => 'view_cooperative_all',
        Reward::class => 'view_cooperative_all',
        PosMemberCreditPayment::class => 'view_cooperative_all',
        PosTransaction::class => 'view_cooperative_all',
        PosVoidRequest::class => 'view_cooperative_all',
        Attendance::class => 'view_attendance_all',
        AttendanceCorrection::class => 'view_attendance_all',
        Asset::class => 'view_asset_all',
        Budget::class => 'view_budget_all',
        Employee::class => 'view_employee_all',
        GoodsReceiveNote::class => 'view_grn_all',
        Invoice::class => 'view_invoice_all',
        Payroll::class => 'view_payroll_all',
        ThrEntitlement::class => 'view_payroll_all',
        Project::class => 'view_project_all',
        PurchaseOrder::class => 'view_po_all',
        PurchaseRequest::class => 'view_pr_all',
        User::class => 'view_user_all',
        WorkOrder::class => 'view_work_order_all',
        MemberStoreAccount::class => 'view_store_credit_all',
        MemberStoreLedgerEntry::class => 'view_store_credit_all',
        MemberStoreFundingRequest::class => 'view_store_credit_all',
        MemberStoreDelegate::class => 'view_store_credit_all',
    ];

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeVisibleTo(Builder $query, User $user, ?string $globalPermission = null): Builder
    {
        $model = $query->getModel();
        $this->pathFor($model);
        $visibility = $this->visibilityFor($user, $globalPermission ?? $this->globalPermissionFor($model));

        return $this->applyVisibility($query, $visibility);
    }

    /**
     * Apply an explicit OrganizationVisibility value object to a query builder.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function applyVisibility(Builder $query, OrganizationVisibility $visibility): Builder
    {
        $model = $query->getModel();
        $path = $this->pathFor($model);

        if ($visibility->state === OrganizationVisibilityState::GLOBAL) {
            return $query;
        }

        if ($visibility->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('Organization visibility is denied.');
        }

        $segments = explode('.', $path);
        $organizationColumn = array_pop($segments);

        if ($segments === []) {
            return $query->where($model->qualifyColumn($organizationColumn), $visibility->organizationId);
        }

        return $query->whereHas(implode('.', $segments), function (Builder $relatedQuery) use ($organizationColumn, $visibility): void {
            $relatedQuery->where($relatedQuery->getModel()->qualifyColumn($organizationColumn), $visibility->organizationId);
        });
    }

    /**
     * Resolve a model by ID within the user's visible organization scope.
     * Scopes the query first, then finds the record or throws ModelNotFoundException (404).
     *
     * @template T of Model
     *
     * @param  Builder<T>|class-string<T>  $queryOrClass
     * @return T
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws OrganizationScopeException
     */
    public function resolveVisible(Builder|string $queryOrClass, User $user, string|int $id, ?string $globalPermission = null): Model
    {
        $query = is_string($queryOrClass) ? $queryOrClass::query() : $queryOrClass;
        $scopedQuery = $this->scopeVisibleTo($query, $user, $globalPermission);

        return $scopedQuery->findOrFail($id);
    }

    public function assertUserHasOrganizationOrGlobal(User $user, ?string $globalPermission = null): void
    {
        $globalPermission ??= PermissionEnum::COOPERATIVE_VIEW_ALL->value;

        if ($this->visibilityFor($user, $globalPermission)->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }
    }

    public function visibilityFor(User $user, ?string $globalPermission = null): OrganizationVisibility
    {
        if ($globalPermission !== null && $user->can($globalPermission)) {
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
        $this->pathFor($model);
        $organizationId = $this->organizationIdForModel($model);
        $visibility = $this->visibilityFor($user, $this->globalPermissionFor($model));

        if ($visibility->state === OrganizationVisibilityState::GLOBAL) {
            return;
        }

        if ((string) $organizationId !== (string) $visibility->organizationId) {
            throw new AuthorizationException('The resource is outside the user organization.');
        }
    }

    public function pathFor(Model $model): string
    {
        $class = get_class($model);

        if ($model instanceof OrganizationScopedModel) {
            $path = $model->organizationScopePath();
            $this->assertPathIsStructurallyValid($model, $path);

            return $path;
        }

        if (isset(self::REGISTERED_PATHS[$class])) {
            $path = self::REGISTERED_PATHS[$class];
            $this->assertPathIsStructurallyValid($model, $path);

            return $path;
        }

        throw new OrganizationScopeException("Model [{$class}] has no explicit organization scope contract.");
    }

    private function assertPathIsStructurallyValid(Model $model, string $path): void
    {
        if ($path === '') {
            throw new OrganizationScopeException('Organization scope paths cannot be empty.');
        }

        $current = $model;
        $segments = explode('.', $path);

        foreach ($segments as $index => $segment) {
            $isLast = $index === array_key_last($segments);

            if ($current->hasAttribute($segment) || $this->modelHasColumn($current, $segment)) {
                if (! $isLast) {
                    throw new OrganizationScopeException("Organization scope path [{$path}] contains an attribute before its final segment.");
                }

                return;
            }

            if (! method_exists($current, $segment)) {
                throw new OrganizationScopeException("Organization scope path [{$path}] is broken for [".get_class($model).'].');
            }

            try {
                $relation = $current->{$segment}();
            } catch (\Throwable $exception) {
                throw new OrganizationScopeException("Organization scope path [{$path}] is broken for [".get_class($model).'].', 0, $exception);
            }

            if (! $relation instanceof Relation) {
                throw new OrganizationScopeException("Organization scope path [{$path}] does not resolve a relationship for [".get_class($model).'].');
            }

            $current = $relation->getRelated();
        }

        throw new OrganizationScopeException("Organization scope path [{$path}] did not resolve a terminal organization column.");
    }

    private function modelHasColumn(Model $model, string $column): bool
    {
        try {
            return $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column);
        } catch (\Throwable $exception) {
            throw new OrganizationScopeException('Unable to validate organization scope schema for ['.get_class($model).'].', 0, $exception);
        }
    }

    public function globalPermissionFor(Model $model): ?string
    {
        return self::GLOBAL_PERMISSIONS[get_class($model)] ?? null;
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function registeredGlobalPermissions(): array
    {
        return self::GLOBAL_PERMISSIONS;
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function registeredPaths(): array
    {
        return self::REGISTERED_PATHS;
    }

    public function assertOrganizationIdentifier(string|int $organizationId): string
    {
        if ((string) $organizationId === '' || ! Organization::query()->whereKey($organizationId)->exists()) {
            throw new AuthorizationException('The selected organization is invalid.');
        }

        return (string) $organizationId;
    }
}
