<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\MedicalCheckup;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;
use App\Models\WorkOrder;
use App\Observers\EmployeeCertificateObserver;
use App\Observers\EmployeeObserver;
use App\Observers\MedicalCheckupObserver;
use App\Policies\AssetPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\CooperativeMemberPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeavePolicy;
use App\Policies\OvertimeRequestPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\WorkOrderPolicy;
use App\Services\Integrations\MidtransPaymentProvider;
use App\Services\Integrations\PaymentGatewayProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayProvider::class, MidtransPaymentProvider::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerRateLimiters();
        $this->registerObservers();
        $this->registerEventListeners();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

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
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(OvertimeRequest::class, OvertimeRequestPolicy::class);
        Gate::policy(Payroll::class, PayrollPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(Reimbursement::class, ReimbursementPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        RateLimiter::for('api-write', fn (Request $request): Limit => Limit::perMinute(30)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }

    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
    }
}
