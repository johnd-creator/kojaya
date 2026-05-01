<?php

namespace Database\Seeders;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Models\Asset;
use App\Models\AssetReading;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Client;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Invoice;
use App\Models\JobGrade;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\MaintenanceSchedule;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\Payroll;
use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use App\Models\Position;
use App\Models\Project;
use App\Models\ProjectBudgetItem;
use App\Models\ProjectDocument;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayrollAllocation;
use App\Models\ProjectTask;
use App\Models\ProjectTeam;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\SparePart;
use App\Models\SparePartStock;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $headOffice = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'address' => 'Jalan Koperasi No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasi.id',
                'is_active' => true,
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'radius' => 200,
            ],
        );

        $jakartaBranch = Organization::query()->firstOrCreate(
            ['code' => 'KOP-101'],
            [
                'id' => (string) Str::uuid(),
                'parent_id' => $headOffice->id,
                'name' => 'Anak Koperasi Jakarta',
                'level' => 'L1',
                'type' => 'BRANCH',
                'address' => 'Jl. Jakarta No. 1',
                'phone' => '021-111111',
                'email' => 'jakarta@koperasi.id',
                'is_active' => true,
                'latitude' => '-6.175392',
                'longitude' => '106.827153',
                'radius' => 150,
            ],
        );

        $bandungBranch = Organization::query()->firstOrCreate(
            ['code' => 'KOP-102'],
            [
                'id' => (string) Str::uuid(),
                'parent_id' => $headOffice->id,
                'name' => 'Anak Koperasi Bandung',
                'level' => 'L1',
                'type' => 'BRANCH',
                'address' => 'Jl. Bandung No. 1',
                'phone' => '022-222222',
                'email' => 'bandung@koperasi.id',
                'is_active' => true,
                'latitude' => '-6.917464',
                'longitude' => '107.619125',
                'radius' => 150,
            ],
        );

        $operationsDepartment = Department::query()->firstOrCreate(
            ['code' => 'DEMO-OPS'],
            [
                'name' => 'Operations',
                'description' => 'Operational demo department',
                'organization_id' => $headOffice->id,
            ],
        );

        $humanResourceDepartment = Department::query()->firstOrCreate(
            ['code' => 'DEMO-HR'],
            [
                'name' => 'Human Resource',
                'description' => 'HR demo department',
                'organization_id' => $headOffice->id,
            ],
        );

        $financeDepartment = Department::query()->firstOrCreate(
            ['code' => 'DEMO-FIN'],
            [
                'name' => 'Finance',
                'description' => 'Finance demo department',
                'organization_id' => $headOffice->id,
            ],
        );

        $staffGrade = JobGrade::query()->firstOrCreate(
            ['code' => 'DEMO-G1'],
            ['name' => 'Staff', 'level' => 1],
        );

        $supervisorGrade = JobGrade::query()->firstOrCreate(
            ['code' => 'DEMO-G3'],
            ['name' => 'Supervisor', 'level' => 3],
        );

        $managerGrade = JobGrade::query()->firstOrCreate(
            ['code' => 'DEMO-G4'],
            ['name' => 'Manager', 'level' => 4],
        );

        $projectManagerPosition = Position::query()->firstOrCreate(
            ['code' => 'DEMO-PM'],
            [
                'name' => 'Project Manager',
                'description' => 'Demo project manager position',
                'department_id' => $operationsDepartment->id,
                'job_grade_id' => $managerGrade->id,
            ],
        );

        $siteManagerPosition = Position::query()->firstOrCreate(
            ['code' => 'DEMO-SM'],
            [
                'name' => 'Site Manager',
                'description' => 'Demo site manager position',
                'department_id' => $operationsDepartment->id,
                'job_grade_id' => $supervisorGrade->id,
            ],
        );

        $technicianPosition = Position::query()->firstOrCreate(
            ['code' => 'DEMO-TECH'],
            [
                'name' => 'Electrical Technician',
                'description' => 'Demo technician position',
                'department_id' => $operationsDepartment->id,
                'job_grade_id' => $staffGrade->id,
            ],
        );

        $financePosition = Position::query()->firstOrCreate(
            ['code' => 'DEMO-FIN-MGR'],
            [
                'name' => 'Finance Lead',
                'description' => 'Demo finance lead position',
                'department_id' => $financeDepartment->id,
                'job_grade_id' => $managerGrade->id,
            ],
        );

        $hrPosition = Position::query()->firstOrCreate(
            ['code' => 'DEMO-HR-MGR'],
            [
                'name' => 'HR Lead',
                'description' => 'Demo HR lead position',
                'department_id' => $humanResourceDepartment->id,
                'job_grade_id' => $managerGrade->id,
            ],
        );

        $officeShift = WorkShift::query()->firstOrCreate(
            ['name' => 'Demo Office Shift', 'type' => 'NON_SHIFT'],
            [
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_flexible' => true,
                'flexible_minutes' => 60,
            ],
        );

        $siteShift = WorkShift::query()->firstOrCreate(
            ['name' => 'Demo Site Shift', 'type' => 'SHIFT'],
            [
                'start_time' => '07:30:00',
                'end_time' => '16:30:00',
                'is_flexible' => false,
                'flexible_minutes' => 15,
            ],
        );

        $annualLeaveType = LeaveType::query()->firstOrCreate(
            ['name' => 'Cuti Tahunan'],
            ['default_days_allowance' => 12, 'requires_attachment' => false, 'is_paid' => true],
        );

        $medicalLeaveType = LeaveType::query()->firstOrCreate(
            ['name' => 'Cuti Sakit'],
            ['default_days_allowance' => 12, 'requires_attachment' => true, 'is_paid' => true],
        );

        $projectManager = $this->upsertDemoEmployeeUser(
            role: 'Project Manager',
            organization: $headOffice,
            department: $operationsDepartment,
            position: $projectManagerPosition,
            jobGrade: $managerGrade,
            workShift: $officeShift,
            attributes: [
                'email' => 'demo.pm@erp.com',
                'name' => 'Demo Project Manager',
                'employee_code' => 'DEMOEMP001',
                'first_name' => 'Raka',
                'last_name' => 'Pratama',
                'gender' => 'M',
                'birth_date' => '1989-04-12',
                'hire_date' => '2021-03-01',
                'phtkp_status' => 'K/2',
                'npwp_number' => '12.345.678.9-012.000',
                'is_npwp_available' => true,
                'number_of_dependents' => 2,
                'bank_account_number' => '7770001001',
            ],
        );

        $siteManager = $this->upsertDemoEmployeeUser(
            role: 'Site Manager',
            organization: $jakartaBranch,
            department: $operationsDepartment,
            position: $siteManagerPosition,
            jobGrade: $supervisorGrade,
            workShift: $siteShift,
            attributes: [
                'email' => 'demo.site@erp.com',
                'name' => 'Demo Site Manager',
                'employee_code' => 'DEMOEMP002',
                'first_name' => 'Anisa',
                'last_name' => 'Putri',
                'gender' => 'F',
                'birth_date' => '1991-08-23',
                'hire_date' => '2022-06-15',
                'phtkp_status' => 'TK/0',
                'npwp_number' => '12.345.678.9-013.000',
                'is_npwp_available' => true,
                'number_of_dependents' => 0,
                'bank_account_number' => '7770001002',
            ],
        );

        $financeLead = $this->upsertDemoEmployeeUser(
            role: 'Finance Pusat',
            organization: $headOffice,
            department: $financeDepartment,
            position: $financePosition,
            jobGrade: $managerGrade,
            workShift: $officeShift,
            attributes: [
                'email' => 'demo.finance@erp.com',
                'name' => 'Demo Finance Lead',
                'employee_code' => 'DEMOEMP003',
                'first_name' => 'Dewi',
                'last_name' => 'Anggraini',
                'gender' => 'F',
                'birth_date' => '1988-11-05',
                'hire_date' => '2020-09-01',
                'phtkp_status' => 'K/1',
                'npwp_number' => '12.345.678.9-014.000',
                'is_npwp_available' => true,
                'number_of_dependents' => 1,
                'bank_account_number' => '7770001003',
            ],
        );

        $humanResourceLead = $this->upsertDemoEmployeeUser(
            role: 'HR Pusat',
            organization: $headOffice,
            department: $humanResourceDepartment,
            position: $hrPosition,
            jobGrade: $managerGrade,
            workShift: $officeShift,
            attributes: [
                'email' => 'demo.hr@erp.com',
                'name' => 'Demo HR Lead',
                'employee_code' => 'DEMOEMP004',
                'first_name' => 'Bima',
                'last_name' => 'Wibowo',
                'gender' => 'M',
                'birth_date' => '1990-01-19',
                'hire_date' => '2021-01-11',
                'phtkp_status' => 'K/1',
                'npwp_number' => '12.345.678.9-015.000',
                'is_npwp_available' => true,
                'number_of_dependents' => 1,
                'bank_account_number' => '7770001004',
            ],
        );

        $technicianOne = $this->upsertDemoEmployeeUser(
            role: 'Karyawan',
            organization: $jakartaBranch,
            department: $operationsDepartment,
            position: $technicianPosition,
            jobGrade: $staffGrade,
            workShift: $siteShift,
            attributes: [
                'email' => 'demo.tech1@erp.com',
                'name' => 'Demo Technician One',
                'employee_code' => 'DEMOEMP005',
                'first_name' => 'Fajar',
                'last_name' => 'Saputra',
                'gender' => 'M',
                'birth_date' => '1996-03-10',
                'hire_date' => '2023-02-01',
                'phtkp_status' => 'TK/0',
                'npwp_number' => null,
                'is_npwp_available' => false,
                'number_of_dependents' => 0,
                'bank_account_number' => '7770001005',
            ],
        );

        $technicianTwo = $this->upsertDemoEmployeeUser(
            role: 'Karyawan',
            organization: $bandungBranch,
            department: $operationsDepartment,
            position: $technicianPosition,
            jobGrade: $staffGrade,
            workShift: $siteShift,
            attributes: [
                'email' => 'demo.tech2@erp.com',
                'name' => 'Demo Technician Two',
                'employee_code' => 'DEMOEMP006',
                'first_name' => 'Nadia',
                'last_name' => 'Maharani',
                'gender' => 'F',
                'birth_date' => '1997-07-14',
                'hire_date' => '2023-05-20',
                'phtkp_status' => 'TK/0',
                'npwp_number' => null,
                'is_npwp_available' => false,
                'number_of_dependents' => 0,
                'bank_account_number' => '7770001006',
            ],
        );

        $plnClient = Client::query()->firstOrCreate(
            ['code' => 'DEMO-CLI-PLN'],
            [
                'name' => 'PT PLN UID Jakarta Demo',
                'address' => 'Jl. Distribusi Tenaga No. 10, Jakarta',
                'tax_id' => '01.234.567.8-999.000',
                'contact_person' => 'Agus Prakoso',
                'phone' => '021-5551000',
                'email' => 'procurement.demo@pln.co.id',
                'client_type' => 'PLN',
                'organization_id' => $headOffice->id,
            ],
        );

        $privateClient = Client::query()->firstOrCreate(
            ['code' => 'DEMO-CLI-PVT'],
            [
                'name' => 'PT Demo Energi Nusantara',
                'address' => 'Jl. Industri Raya No. 88, Bandung',
                'tax_id' => '01.234.567.8-998.000',
                'contact_person' => 'Larasati Ningsih',
                'phone' => '022-5552000',
                'email' => 'projects@demoenergi.co.id',
                'client_type' => 'PRIVATE',
                'organization_id' => $headOffice->id,
            ],
        );

        $activeProject = Project::query()->firstOrCreate(
            ['project_code' => 'DEMO-PRJ-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Revitalisasi Gardu Distribusi Jakarta Timur',
                'description' => 'Project demo untuk pengujian modul project, asset, finance, dan manpower.',
                'organization_id' => $headOffice->id,
                'client_id' => $plnClient->id,
                'start_date' => '2026-04-01',
                'end_date' => '2026-07-31',
                'budget' => 2750000000,
                'actual_cost' => 1235000000,
                'status' => 'ONGOING',
                'progress_percentage' => 46,
                'notes' => 'Project demo aktif dengan data lintas modul.',
            ],
        );

        Project::query()->firstOrCreate(
            ['project_code' => 'DEMO-PRJ-002'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Maintenance Panel Cabang Bandung',
                'description' => 'Project demo kedua untuk pengujian status planning.',
                'organization_id' => $headOffice->id,
                'client_id' => $privateClient->id,
                'start_date' => '2026-08-10',
                'end_date' => '2026-10-15',
                'budget' => 950000000,
                'actual_cost' => 0,
                'status' => 'PLANNING',
                'progress_percentage' => 5,
                'notes' => 'Belum dimulai sepenuhnya.',
            ],
        );

        ProjectTeam::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'employee_id' => $projectManager['employee']->id],
            [
                'role' => 'Project Director',
                'start_date' => '2026-04-01',
                'end_date' => '2026-07-31',
                'daily_rate_cost' => 1750000,
                'notes' => 'Leads stakeholder coordination.',
                'status' => 'PLACED',
                'has_ppe' => true,
                'has_uniform' => true,
            ],
        );

        ProjectTeam::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'employee_id' => $siteManager['employee']->id],
            [
                'role' => 'Site Lead',
                'start_date' => '2026-04-01',
                'end_date' => '2026-07-31',
                'daily_rate_cost' => 1250000,
                'notes' => 'Coordinates field execution and safety.',
                'status' => 'PLACED',
                'has_ppe' => true,
                'has_uniform' => true,
            ],
        );

        ProjectTeam::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'employee_id' => $technicianOne['employee']->id],
            [
                'role' => 'Electrical Technician',
                'start_date' => '2026-04-10',
                'end_date' => '2026-07-20',
                'daily_rate_cost' => 650000,
                'notes' => 'Handles field installation and testing.',
                'status' => 'PLACED',
                'has_ppe' => true,
                'has_uniform' => true,
            ],
        );

        ProjectTeam::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'employee_id' => $technicianTwo['employee']->id],
            [
                'role' => 'Testing Technician',
                'start_date' => '2026-04-15',
                'end_date' => '2026-07-15',
                'daily_rate_cost' => 625000,
                'notes' => 'Supports QA and commissioning.',
                'status' => 'ONBOARDING',
                'has_ppe' => true,
                'has_uniform' => false,
            ],
        );

        ProjectTask::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Site survey and kick-off'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Initial site survey, risk review, and kick-off meeting.',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-05',
                'assigned_to' => $siteManager['employee']->id,
                'status' => 'COMPLETED',
                'progress_percentage' => 100,
                'estimated_hours' => 32,
                'actual_hours' => 30,
                'sort_order' => 1,
            ],
        );

        $procurementTask = ProjectTask::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Material procurement'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Procurement and staging of replacement materials.',
                'start_date' => '2026-04-06',
                'end_date' => '2026-04-25',
                'assigned_to' => $projectManager['employee']->id,
                'status' => 'COMPLETED',
                'progress_percentage' => 100,
                'estimated_hours' => 80,
                'actual_hours' => 76,
                'sort_order' => 2,
            ],
        );

        ProjectTask::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Cable tray installation'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Install cable trays and supporting accessories.',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-20',
                'assigned_to' => $technicianOne['employee']->id,
                'status' => 'IN_PROGRESS',
                'progress_percentage' => 65,
                'estimated_hours' => 120,
                'actual_hours' => 74,
                'sort_order' => 3,
            ],
        );

        ProjectTask::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Commissioning and handover'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Testing, punch list closure, and handover documentation.',
                'parent_task_id' => $procurementTask->id,
                'start_date' => '2026-06-20',
                'end_date' => '2026-07-31',
                'assigned_to' => $technicianTwo['employee']->id,
                'status' => 'PENDING',
                'progress_percentage' => 10,
                'estimated_hours' => 96,
                'actual_hours' => 0,
                'sort_order' => 4,
            ],
        );

        ProjectMilestone::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Material on site'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Main materials have been delivered to site.',
                'due_date' => '2026-04-25',
                'status' => 'COMPLETED',
                'progress_percentage' => 100,
            ],
        );

        ProjectMilestone::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'Mechanical completion'],
            [
                'id' => (string) Str::uuid(),
                'description' => 'Mechanical installation reaches 100%.',
                'due_date' => '2026-06-15',
                'status' => 'IN_PROGRESS',
                'progress_percentage' => 72,
            ],
        );

        ProjectDocument::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'SIKA Induk Demo'],
            [
                'id' => (string) Str::uuid(),
                'type' => 'SIKA',
                'file_path' => 'demo/documents/sika-induk-demo.pdf',
                'expiry_date' => '2026-07-31',
                'status' => 'VALID',
            ],
        );

        ProjectDocument::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'name' => 'As Built Drawing Rev A'],
            [
                'id' => (string) Str::uuid(),
                'type' => 'DRAWING',
                'file_path' => 'demo/documents/as-built-drawing-rev-a.pdf',
                'expiry_date' => null,
                'status' => 'VALID',
            ],
        );

        $budget = Budget::query()->firstOrCreate(
            ['organization_id' => $headOffice->id, 'year' => '2026', 'period' => 'ANNUAL'],
            ['id' => (string) Str::uuid(), 'status' => 'APPROVED'],
        );

        BudgetLine::query()->firstOrCreate(
            ['budget_id' => $budget->id, 'gl_account' => '5100-OPS-MAT'],
            [
                'id' => (string) Str::uuid(),
                'cost_center' => 'OPS-JKT',
                'project_id' => $activeProject->id,
                'category' => 'CAPEX',
                'allocated_amount' => 1500000000,
                'committed_amount' => 950000000,
                'realized_amount' => 845000000,
            ],
        );

        BudgetLine::query()->firstOrCreate(
            ['budget_id' => $budget->id, 'gl_account' => '5200-OPS-LAB'],
            [
                'id' => (string) Str::uuid(),
                'cost_center' => 'OPS-JKT',
                'project_id' => $activeProject->id,
                'category' => 'OPEX',
                'allocated_amount' => 600000000,
                'committed_amount' => 285000000,
                'realized_amount' => 210000000,
            ],
        );

        ProjectBudgetItem::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'category' => 'MATERIAL', 'description' => 'Cable, panel, and accessories'],
            ['planned_amount' => 1450000000, 'actual_amount' => 865000000],
        );

        ProjectBudgetItem::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'category' => 'LABOR', 'description' => 'Field labor and supervision'],
            ['planned_amount' => 520000000, 'actual_amount' => 280000000],
        );

        ProjectBudgetItem::query()->firstOrCreate(
            ['project_id' => $activeProject->id, 'category' => 'EQUIPMENT', 'description' => 'Lifting and testing equipment'],
            ['planned_amount' => 330000000, 'actual_amount' => 90000000],
        );

        Invoice::query()->firstOrCreate(
            ['organization_id' => $headOffice->id, 'invoice_no' => 'DEMO-INV-2026-001'],
            [
                'unit_id' => $jakartaBranch->id,
                'client_id' => $plnClient->id,
                'project_id' => $activeProject->id,
                'invoice_date' => '2026-04-30',
                'due_date' => '2026-05-30',
                'amount' => 750000000,
                'tax_amount' => 82500000,
                'total_amount' => 832500000,
                'status' => 'PAID',
                'notes' => 'Progress billing termin 1.',
            ],
        );

        Invoice::query()->firstOrCreate(
            ['organization_id' => $headOffice->id, 'invoice_no' => 'DEMO-INV-2026-002'],
            [
                'unit_id' => $jakartaBranch->id,
                'client_id' => $plnClient->id,
                'project_id' => $activeProject->id,
                'invoice_date' => '2026-05-31',
                'due_date' => '2026-06-30',
                'amount' => 680000000,
                'tax_amount' => 74800000,
                'total_amount' => 754800000,
                'status' => 'PENDING',
                'notes' => 'Progress billing termin 2.',
            ],
        );

        $pettyCashAccount = PettyCashAccount::query()->firstOrCreate(
            ['organization_id' => $headOffice->id, 'name' => 'Petty Cash Operasional Demo'],
            [
                'balance' => 35000000,
                'limit' => 50000000,
                'status' => 'ACTIVE',
                'description' => 'Dana operasional harian untuk kebutuhan project demo.',
            ],
        );

        PettyCashTransaction::query()->firstOrCreate(
            ['petty_cash_account_id' => $pettyCashAccount->id, 'reference_no' => 'DEMO-PC-001'],
            [
                'user_id' => $financeLead['user']->id,
                'project_id' => $activeProject->id,
                'transaction_date' => '2026-05-08',
                'type' => 'DEBIT',
                'amount' => 4200000,
                'description' => 'Pembelian consumable lapangan dan APD tambahan.',
                'status' => 'APPROVED',
                'proof_file' => 'demo/receipts/petty-cash-001.pdf',
            ],
        );

        PettyCashTransaction::query()->firstOrCreate(
            ['petty_cash_account_id' => $pettyCashAccount->id, 'reference_no' => 'DEMO-PC-002'],
            [
                'user_id' => $financeLead['user']->id,
                'project_id' => $activeProject->id,
                'transaction_date' => '2026-05-15',
                'type' => 'CREDIT',
                'amount' => 10000000,
                'description' => 'Top up petty cash project demo.',
                'status' => 'APPROVED',
                'proof_file' => 'demo/receipts/petty-cash-002.pdf',
            ],
        );

        $reimbursement = Reimbursement::query()->firstOrCreate(
            ['organization_id' => $headOffice->id, 'user_id' => $siteManager['user']->id, 'submission_date' => '2026-05-10'],
            [
                'project_id' => $activeProject->id,
                'approver_id' => $financeLead['user']->id,
                'total_amount' => 1850000,
                'status' => 'APPROVED',
                'description' => 'Transport lokal dan kebutuhan koordinasi lapangan.',
                'payment_date' => '2026-05-14',
            ],
        );

        ReimbursementItem::query()->firstOrCreate(
            ['reimbursement_id' => $reimbursement->id, 'description' => 'Transport survey lapangan'],
            [
                'category' => 'TRANSPORT',
                'amount' => 650000,
                'receipt_file_path' => 'demo/receipts/reimbursement-transport.pdf',
                'receipt_date' => '2026-05-09',
            ],
        );

        ReimbursementItem::query()->firstOrCreate(
            ['reimbursement_id' => $reimbursement->id, 'description' => 'Konsumsi koordinasi vendor'],
            [
                'category' => 'MEAL',
                'amount' => 1200000,
                'receipt_file_path' => 'demo/receipts/reimbursement-meal.pdf',
                'receipt_date' => '2026-05-10',
            ],
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            ['code' => 'DEMO-WH-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Gudang Operasional Jakarta Demo',
                'organization_id' => $jakartaBranch->id,
                'location' => 'Area Workshop Jakarta Timur',
                'type' => 'STORAGE',
                'is_active' => true,
            ],
        );

        $cableLug = SparePart::query()->firstOrCreate(
            ['code' => 'DEMO-SP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Cable Lug 240mm',
                'specification' => 'Copper lug for panel connection',
                'unit' => 'PCS',
                'min_stock' => 20,
                'max_stock' => 200,
                'reorder_level' => 40,
                'category' => 'Electrical',
                'organization_id' => $jakartaBranch->id,
                'is_active' => true,
            ],
        );

        $mcbPart = SparePart::query()->firstOrCreate(
            ['code' => 'DEMO-SP-002'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'MCB 3P 125A',
                'specification' => 'Spare breaker for distribution panel',
                'unit' => 'PCS',
                'min_stock' => 5,
                'max_stock' => 25,
                'reorder_level' => 8,
                'category' => 'Protection',
                'organization_id' => $jakartaBranch->id,
                'is_active' => true,
            ],
        );

        SparePartStock::query()->firstOrCreate(
            ['spare_part_id' => $cableLug->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 120, 'reserved_quantity' => 20],
        );

        SparePartStock::query()->firstOrCreate(
            ['spare_part_id' => $mcbPart->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 12, 'reserved_quantity' => 2],
        );

        $transformerAsset = Asset::query()->firstOrCreate(
            ['code' => 'DEMO-AST-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Trafo Distribusi 250 kVA',
                'category' => 'Transformer',
                'organization_id' => $jakartaBranch->id,
                'status' => 'ACTIVE',
                'purchase_date' => '2025-09-12',
                'serial_number' => 'TRF-250KVA-DEMO-01',
            ],
        );

        $gensetAsset = Asset::query()->firstOrCreate(
            ['code' => 'DEMO-AST-002'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Genset Mobile 200 kVA',
                'category' => 'Generator',
                'organization_id' => $jakartaBranch->id,
                'status' => 'UNDER_MAINTENANCE',
                'purchase_date' => '2024-11-03',
                'serial_number' => 'GEN-200KVA-DEMO-02',
            ],
        );

        AssetReading::query()->firstOrCreate(
            ['asset_id' => $gensetAsset->id, 'reading_value' => 1280.5],
            ['reading_unit' => 'hours', 'recorded_at' => '2026-05-20 09:15:00'],
        );

        MaintenanceSchedule::query()->firstOrCreate(
            ['asset_id' => $gensetAsset->id, 'frequency' => 'MONTHLY'],
            [
                'type' => 'TIME_BASED',
                'interval_value' => 1,
                'maintenance_checklist_id' => null,
                'next_due_date' => '2026-06-05',
                'last_meter_reading' => 1280.5,
                'target_meter_reading' => null,
                'priority' => 'HIGH',
                'assigned_to' => $siteManager['user']->id,
                'instructions' => 'Routine preventive maintenance for generator.',
                'is_active' => true,
                'last_completed_at' => '2026-05-05 10:00:00',
            ],
        );

        WorkOrder::query()->firstOrCreate(
            ['asset_id' => $gensetAsset->id, 'description' => 'Routine oil and filter replacement'],
            [
                'organization_id' => $jakartaBranch->id,
                'type' => 'PREVENTIVE',
                'priority' => 'HIGH',
                'status' => 'IN_PROGRESS',
                'assigned_to' => $siteManager['user']->id,
                'completed_at' => null,
            ],
        );

        WorkOrder::query()->firstOrCreate(
            ['asset_id' => $transformerAsset->id, 'description' => 'Infrared thermography inspection'],
            [
                'organization_id' => $jakartaBranch->id,
                'type' => 'CORRECTIVE',
                'priority' => 'MEDIUM',
                'status' => 'OPEN',
                'assigned_to' => $technicianOne['user']->id,
                'completed_at' => null,
            ],
        );

        $today = Carbon::today();
        $attendanceEmployees = [
            $siteManager['employee'],
            $technicianOne['employee'],
            $technicianTwo['employee'],
        ];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $today->copy()->subDays($offset);

            if ($date->isSunday()) {
                continue;
            }

            foreach ($attendanceEmployees as $employee) {
                $status = 'PRESENT';
                $clockIn = '07:35:00';
                $clockOut = '16:40:00';
                $notes = null;

                if ($employee->is($technicianTwo['employee']) && $offset === 2) {
                    $status = 'LEAVE';
                    $clockIn = null;
                    $clockOut = null;
                    $notes = 'On approved annual leave.';
                }

                if ($employee->is($siteManager['employee']) && $offset === 4) {
                    $status = 'SICK';
                    $clockIn = null;
                    $clockOut = null;
                    $notes = 'Medical rest.';
                }

                Attendance::query()->firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'organization_id' => $employee->organization_id,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'notes' => $notes,
                    ],
                );
            }
        }

        Leave::query()->firstOrCreate(
            ['employee_id' => $technicianTwo['employee']->id, 'start_date' => '2026-05-19'],
            [
                'leave_type_id' => $annualLeaveType->id,
                'end_date' => '2026-05-20',
                'total_days' => 2,
                'reason' => 'Family event in hometown.',
                'status' => 'Approved',
                'approver_id' => $humanResourceLead['user']->id,
            ],
        );

        Leave::query()->firstOrCreate(
            ['employee_id' => $siteManager['employee']->id, 'start_date' => '2026-06-03'],
            [
                'leave_type_id' => $medicalLeaveType->id,
                'end_date' => '2026-06-03',
                'total_days' => 1,
                'reason' => 'Medical follow-up appointment.',
                'status' => 'Pending',
                'approver_id' => null,
            ],
        );

        $overtimeRule = OvertimeRule::query()->firstOrCreate(
            ['organization_id' => $jakartaBranch->id, 'code' => 'DEMO-OT-001'],
            [
                'name' => 'Weekday Field Overtime',
                'description' => 'Standard overtime rule for project demo.',
                'multiplier' => 1.50,
                'min_hours' => 1.00,
                'max_hours_daily' => 4.00,
                'max_hours_monthly' => 40.00,
                'is_weekday' => true,
                'is_holiday' => false,
                'requires_approval' => true,
                'is_active' => true,
            ],
        );

        OvertimeRequest::query()->firstOrCreate(
            ['employee_id' => $technicianOne['employee']->id, 'date' => '2026-05-18'],
            [
                'organization_id' => $jakartaBranch->id,
                'overtime_rule_id' => $overtimeRule->id,
                'start_time' => '17:30:00',
                'end_time' => '20:30:00',
                'total_hours' => 3.00,
                'reason' => 'Cable termination catch-up after vendor delay.',
                'status' => 'APPROVED',
                'approved_by' => $siteManager['user']->id,
                'approved_at' => '2026-05-18 21:00:00',
                'rejection_reason' => null,
            ],
        );

        $payrollPeriod = '2026-05';

        $projectManagerPayroll = Payroll::query()->firstOrCreate(
            ['employee_id' => $projectManager['employee']->id, 'period' => $payrollPeriod],
            [
                'organization_id' => $headOffice->id,
                'basic_salary' => 18500000,
                'total_allowance' => 3250000,
                'total_deduction' => 1750000,
                'tax_amount' => 950000,
                'bpjs_amount' => 425000,
                'net_salary' => 18625000,
                'status' => 'PROCESSED',
            ],
        );

        $siteManagerPayroll = Payroll::query()->firstOrCreate(
            ['employee_id' => $siteManager['employee']->id, 'period' => $payrollPeriod],
            [
                'organization_id' => $jakartaBranch->id,
                'basic_salary' => 11250000,
                'total_allowance' => 1750000,
                'total_deduction' => 950000,
                'tax_amount' => 375000,
                'bpjs_amount' => 265000,
                'net_salary' => 11410000,
                'status' => 'PROCESSED',
            ],
        );

        ProjectPayrollAllocation::query()->firstOrCreate(
            ['payroll_id' => $projectManagerPayroll->id, 'project_id' => $activeProject->id],
            ['amount' => 9250000, 'notes' => '50% of PM cost allocated to active project.'],
        );

        ProjectPayrollAllocation::query()->firstOrCreate(
            ['payroll_id' => $siteManagerPayroll->id, 'project_id' => $activeProject->id],
            ['amount' => 11250000, 'notes' => 'Full site manager cost allocated to active project.'],
        );

        EmployeeCertificate::query()->firstOrCreate(
            ['employee_id' => $siteManager['employee']->id, 'certificate_number' => 'DEMO-SIO-001'],
            [
                'certificate_type' => CertificateType::SIO_K3,
                'issue_date' => '2025-08-01',
                'expiry_date' => '2026-08-01',
                'issuing_authority' => 'Kemnaker',
                'document_path' => 'demo/certificates/sio-site-manager.pdf',
                'status' => CertificateStatus::VALID,
                'notes' => 'Field supervisor safety operator certificate.',
            ],
        );

        EmployeeCertificate::query()->firstOrCreate(
            ['employee_id' => $technicianOne['employee']->id, 'certificate_number' => 'DEMO-TRN-001'],
            [
                'certificate_type' => CertificateType::TRAINING,
                'issue_date' => '2025-09-15',
                'expiry_date' => '2026-06-15',
                'issuing_authority' => 'Internal Training Center',
                'document_path' => 'demo/certificates/training-tech1.pdf',
                'status' => CertificateStatus::VALID,
                'notes' => 'Internal electrical safety refresh training.',
            ],
        );

        MedicalCheckup::query()->firstOrCreate(
            ['employee_id' => $siteManager['employee']->id, 'checkup_date' => '2025-10-05'],
            [
                'next_checkup_date' => '2026-10-05',
                'result' => McuResult::FIT,
                'fit_to_work' => true,
                'notes' => 'All parameters within acceptable range.',
                'document_path' => 'demo/mcu/site-manager-2025.pdf',
                'doctor_name' => 'dr. Maya Putri',
                'clinic_name' => 'Klinik Sehat Kerja',
            ],
        );

        MedicalCheckup::query()->firstOrCreate(
            ['employee_id' => $technicianTwo['employee']->id, 'checkup_date' => '2025-11-12'],
            [
                'next_checkup_date' => '2026-06-12',
                'result' => McuResult::FIT_WITH_RESTRICTION,
                'fit_to_work' => true,
                'notes' => 'Monitor fatigue level and provide regular breaks.',
                'document_path' => 'demo/mcu/technician-two-2025.pdf',
                'doctor_name' => 'dr. Rina Kurnia',
                'clinic_name' => 'Klinik Sehat Kerja',
            ],
        );

        AuditLog::query()->firstOrCreate(
            ['user_id' => $projectManager['user']->id, 'action' => 'CREATE', 'module' => 'projects'],
            [
                'subject_type' => null,
                'subject_id' => null,
                'old_values' => null,
                'new_values' => ['project_code' => $activeProject->project_code, 'status' => $activeProject->status],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Demo Seeder',
            ],
        );

        AuditLog::query()->firstOrCreate(
            ['user_id' => $financeLead['user']->id, 'action' => 'UPDATE', 'module' => 'invoices'],
            [
                'subject_type' => null,
                'subject_id' => null,
                'old_values' => ['status' => 'APPROVED'],
                'new_values' => ['status' => 'PAID'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Demo Seeder',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{user: \App\Models\User, employee: \App\Models\Employee}
     */
    private function upsertDemoEmployeeUser(
        string $role,
        Organization $organization,
        Department $department,
        Position $position,
        JobGrade $jobGrade,
        WorkShift $workShift,
        array $attributes,
    ): array {
        $user = User::query()->updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'password' => 'password',
                'organization_id' => $organization->id,
            ],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (Role::query()->where('name', $role)->exists()) {
            $user->syncRoles([$role]);
        }

        $employee = Employee::query()->updateOrCreate(
            ['employee_code' => $attributes['employee_code']],
            [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => $attributes['email'],
                'gender' => $attributes['gender'],
                'birth_date' => $attributes['birth_date'],
                'hire_date' => $attributes['hire_date'],
                'status' => 'ACTIVE',
                'employee_type' => 'Organic',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'job_grade_id' => $jobGrade->id,
                'work_shift_id' => $workShift->id,
                'shift_group' => null,
                'phtkp_status' => $attributes['phtkp_status'],
                'npwp_number' => $attributes['npwp_number'],
                'is_npwp_available' => $attributes['is_npwp_available'],
                'number_of_dependents' => $attributes['number_of_dependents'],
                'bank_name' => 'BCA',
                'bank_account_number' => $attributes['bank_account_number'],
                'bank_account_holder' => $attributes['name'],
            ],
        );

        return [
            'user' => $user,
            'employee' => $employee,
        ];
    }
}
