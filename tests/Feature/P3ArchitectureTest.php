<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\PayrollApproval;
use App\Models\PettyCashAccount;
use App\Models\Project;
use App\Models\ProjectAssetAllocation;
use App\Models\ProjectBudgetItem;
use App\Models\ProjectDocument;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayrollAllocation;
use App\Models\ProjectTask;
use App\Models\ProjectTeam;
use App\Models\ReimbursementItem;
use App\Models\SalaryStructure;
use App\Models\SparePart;
use App\Models\Traits\HasOrganizationScope;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;
use Tests\TestCase;

class P3ArchitectureTest extends TestCase
{
    public function test_p3_uuid_models_use_has_uuids_trait(): void
    {
        $uuidModels = [
            Client::class,
            Invoice::class,
            PayrollApproval::class,
            Project::class,
            ProjectDocument::class,
            ProjectMilestone::class,
            ProjectTask::class,
            ProjectTeam::class,
        ];

        foreach ($uuidModels as $modelClass) {
            $this->assertContains(HasUuids::class, class_uses_recursive($modelClass));
        }
    }

    public function test_p3_organization_scoped_models_use_shared_scope_trait(): void
    {
        $scopedModels = [
            Attendance::class,
            Client::class,
            Department::class,
            PettyCashAccount::class,
            Project::class,
            SalaryStructure::class,
            SparePart::class,
            Warehouse::class,
        ];

        foreach ($scopedModels as $modelClass) {
            $this->assertContains(HasOrganizationScope::class, class_uses_recursive($modelClass));
        }
    }

    public function test_p3_missing_relationships_are_available_with_expected_types(): void
    {
        $warehouse = new Warehouse;
        $organization = new Organization;
        $user = new User;

        $this->assertInstanceOf(HasMany::class, $warehouse->purchaseOrders());
        $this->assertInstanceOf(HasMany::class, $warehouse->goodsReceiveNotes());

        $this->assertInstanceOf(HasMany::class, $organization->departments());
        $this->assertInstanceOf(HasMany::class, $organization->projects());
        $this->assertInstanceOf(HasMany::class, $organization->invoices());
        $this->assertInstanceOf(HasMany::class, $organization->employees());
        $this->assertInstanceOf(HasMany::class, $organization->assets());
        $this->assertInstanceOf(HasMany::class, $organization->workOrders());
        $this->assertInstanceOf(HasMany::class, $organization->budgets());
        $this->assertInstanceOf(HasMany::class, $organization->vendors());

        $this->assertInstanceOf(HasMany::class, $user->auditLogs());
    }

    public function test_p3_support_models_define_local_casts_method(): void
    {
        $models = [
            MedicalCheckup::class,
            ProjectAssetAllocation::class,
            ProjectBudgetItem::class,
            ProjectDocument::class,
            ProjectPayrollAllocation::class,
            ReimbursementItem::class,
        ];

        foreach ($models as $modelClass) {
            $reflection = new ReflectionClass($modelClass);

            $this->assertTrue($reflection->hasMethod('casts'));
            $this->assertSame($modelClass, $reflection->getMethod('casts')->class);
        }
    }
}
