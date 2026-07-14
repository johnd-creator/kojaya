<?php

namespace App\Providers;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Contracts\Integrations\PaymentGatewayProvider as PaymentGatewayProviderContract;
use App\Listeners\FailedJobListener;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\LoanRestructure;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;
use App\Models\RewardRedemption;
use App\Models\SavingsWithdrawal;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Observers\EmployeeCertificateObserver;
use App\Observers\EmployeeObserver;
use App\Observers\MedicalCheckupObserver;
use App\Policies\AssetPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\CooperativeMemberPolicy;
use App\Policies\CooperativePaymentPolicy;
use App\Policies\CooperativeShuPeriodPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeavePolicy;
use App\Policies\LoanPolicy;
use App\Policies\LoanRestructurePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\OvertimeRequestPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\RewardRedemptionPolicy;
use App\Policies\SavingsWithdrawalPolicy;
use App\Policies\VendorPolicy;
use App\Policies\WorkOrderPolicy;
use App\Services\Cooperative\LoanService;
use App\Services\Integrations\MidtransPaymentProvider;
use App\Services\Integrations\PaymentGatewayProvider;
use App\Services\Security\PiiCryptoService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoanServiceContract::class, LoanService::class);
        $this->app->bind(PaymentGatewayProviderContract::class, MidtransPaymentProvider::class);
        $this->app->bind(PaymentGatewayProvider::class, MidtransPaymentProvider::class);
        $this->app->singleton(PiiCryptoService::class, fn (): PiiCryptoService => new PiiCryptoService(
            config('security.encryption_keys', []),
            (string) config('security.encryption_current_version', 'v1'),
            config('security.blind_index_keys', []),
            (string) config('security.blind_index_current_version', 'v1'),
            config('security.legacy_encryption_key'),
            config('security.blind_index_active_versions'),
            (string) config('security.rollout_phase', PiiCryptoService::ROLLOUT_DUAL_WRITE),
        ));
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerRateLimiters();
        $this->registerObservers();
        $this->registerEventListeners();
        $this->registerJobListeners();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerObservers(): void
    {
        Employee::observe(EmployeeObserver::class);
        EmployeeCertificate::observe(EmployeeCertificateObserver::class);
        MedicalCheckup::observe(MedicalCheckupObserver::class);
    }

    protected function registerPolicies(): void
    {
        Gate::before(fn ($user): ?bool => $user->hasRole('System Admin') ? true : null);

        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(CooperativeMember::class, CooperativeMemberPolicy::class);
        Gate::policy(CooperativePayment::class, CooperativePaymentPolicy::class);
        Gate::policy(CooperativeShuPeriod::class, CooperativeShuPeriodPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(Loan::class, LoanPolicy::class);
        Gate::policy(LoanRestructure::class, LoanRestructurePolicy::class);
        Gate::policy(OvertimeRequest::class, OvertimeRequestPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Payroll::class, PayrollPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(Reimbursement::class, ReimbursementPolicy::class);
        Gate::policy(RewardRedemption::class, RewardRedemptionPolicy::class);
        Gate::policy(SavingsWithdrawal::class, SavingsWithdrawalPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
    }

    protected function registerRateLimiters(): void
    {
        // The mobile client legitimately bursts (a single dashboard mount
        // triggers several read endpoints). Keep the limit per-user so it
        // still protects against abuse, but give enough headroom for normal
        // navigation and development restarts.
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(180)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('api-write', fn (Request $request): Limit => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        // Audit log read endpoints can be expensive (full table scans, joins);
        // tighten the read limit so a single account/IP cannot abuse them.
        RateLimiter::for('audit-logs', fn (Request $request): Limit => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));

        // Export endpoints fetch up to 1k rows. Tightly throttle to discourage
        // bulk-scraping the audit trail.
        RateLimiter::for('audit-export', fn (Request $request): Limit => Limit::perMinute(5)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }

    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
    }

    protected function registerJobListeners(): void
    {
        Queue::failing(function (JobFailed $event) {
            (new FailedJobListener)->handle($event);
        });
    }
}
