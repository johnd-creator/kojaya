import Api from './Api'
import OpenApiController from './OpenApiController'
import Cooperative from './Cooperative'
import EmployeeCertificateController from './EmployeeCertificateController'
import MedicalCheckupController from './MedicalCheckupController'
import ComplianceReportController from './ComplianceReportController'
import AuditLogController from './AuditLogController'
import Auth from './Auth'
import DashboardController from './DashboardController'
import ReportController from './ReportController'
import NotificationController from './NotificationController'
import DepartmentController from './DepartmentController'
import JobGradeController from './JobGradeController'
import PositionController from './PositionController'
import WorkShiftController from './WorkShiftController'
import SalaryStructureController from './SalaryStructureController'
import ShiftRosterController from './ShiftRosterController'
import SparePartController from './SparePartController'
import WarehouseController from './WarehouseController'
import AssetController from './AssetController'
import WorkOrderController from './WorkOrderController'
import UserController from './UserController'
import RoleController from './RoleController'
import OrganizationController from './OrganizationController'
import SwitchOrganizationController from './SwitchOrganizationController'
import ClientController from './ClientController'
import EmployeeController from './EmployeeController'
import EmployeeTransferController from './EmployeeTransferController'
import AttendanceController from './AttendanceController'
import LeaveController from './LeaveController'
import OvertimeController from './OvertimeController'
import EssPortalController from './EssPortalController'
import MemberPortalController from './MemberPortalController'
import EmployeeContractController from './EmployeeContractController'
import PayrollController from './PayrollController'
import PayrollApprovalController from './PayrollApprovalController'
import InvoiceController from './InvoiceController'
import EfakturController from './EfakturController'
import EfakturApiController from './EfakturApiController'
import EfakturUiController from './EfakturUiController'
import BudgetController from './BudgetController'
import BudgetLineController from './BudgetLineController'
import PettyCashAccountController from './PettyCashAccountController'
import PettyCashTransactionController from './PettyCashTransactionController'
import FinanceBankController from './FinanceBankController'
import BankReconciliationController from './BankReconciliationController'
import Accounting from './Accounting'
import Finance from './Finance'
import ExceptionReportController from './ExceptionReportController'
import Monitoring from './Monitoring'
import DocumentDownloadController from './DocumentDownloadController'
import ReimbursementController from './ReimbursementController'
import Procurement from './Procurement'
import ProjectController from './ProjectController'
import ProjectFinanceController from './ProjectFinanceController'
import ProjectGanttController from './ProjectGanttController'
import ProjectTaskController from './ProjectTaskController'
import ProjectTeamController from './ProjectTeamController'
import ProjectMilestoneController from './ProjectMilestoneController'
import ProjectResourceController from './ProjectResourceController'
import ProjectDocumentController from './ProjectDocumentController'
import Settings from './Settings'

const Controllers = {
    Api: Object.assign(Api, Api),
    OpenApiController: Object.assign(OpenApiController, OpenApiController),
    Cooperative: Object.assign(Cooperative, Cooperative),
    EmployeeCertificateController: Object.assign(EmployeeCertificateController, EmployeeCertificateController),
    MedicalCheckupController: Object.assign(MedicalCheckupController, MedicalCheckupController),
    ComplianceReportController: Object.assign(ComplianceReportController, ComplianceReportController),
    AuditLogController: Object.assign(AuditLogController, AuditLogController),
    Auth: Object.assign(Auth, Auth),
    DashboardController: Object.assign(DashboardController, DashboardController),
    ReportController: Object.assign(ReportController, ReportController),
    NotificationController: Object.assign(NotificationController, NotificationController),
    DepartmentController: Object.assign(DepartmentController, DepartmentController),
    JobGradeController: Object.assign(JobGradeController, JobGradeController),
    PositionController: Object.assign(PositionController, PositionController),
    WorkShiftController: Object.assign(WorkShiftController, WorkShiftController),
    SalaryStructureController: Object.assign(SalaryStructureController, SalaryStructureController),
    ShiftRosterController: Object.assign(ShiftRosterController, ShiftRosterController),
    SparePartController: Object.assign(SparePartController, SparePartController),
    WarehouseController: Object.assign(WarehouseController, WarehouseController),
    AssetController: Object.assign(AssetController, AssetController),
    WorkOrderController: Object.assign(WorkOrderController, WorkOrderController),
    UserController: Object.assign(UserController, UserController),
    RoleController: Object.assign(RoleController, RoleController),
    OrganizationController: Object.assign(OrganizationController, OrganizationController),
    SwitchOrganizationController: Object.assign(SwitchOrganizationController, SwitchOrganizationController),
    ClientController: Object.assign(ClientController, ClientController),
    EmployeeController: Object.assign(EmployeeController, EmployeeController),
    EmployeeTransferController: Object.assign(EmployeeTransferController, EmployeeTransferController),
    AttendanceController: Object.assign(AttendanceController, AttendanceController),
    LeaveController: Object.assign(LeaveController, LeaveController),
    OvertimeController: Object.assign(OvertimeController, OvertimeController),
    EssPortalController: Object.assign(EssPortalController, EssPortalController),
    MemberPortalController: Object.assign(MemberPortalController, MemberPortalController),
    EmployeeContractController: Object.assign(EmployeeContractController, EmployeeContractController),
    PayrollController: Object.assign(PayrollController, PayrollController),
    PayrollApprovalController: Object.assign(PayrollApprovalController, PayrollApprovalController),
    InvoiceController: Object.assign(InvoiceController, InvoiceController),
    EfakturController: Object.assign(EfakturController, EfakturController),
    EfakturApiController: Object.assign(EfakturApiController, EfakturApiController),
    EfakturUiController: Object.assign(EfakturUiController, EfakturUiController),
    BudgetController: Object.assign(BudgetController, BudgetController),
    BudgetLineController: Object.assign(BudgetLineController, BudgetLineController),
    PettyCashAccountController: Object.assign(PettyCashAccountController, PettyCashAccountController),
    PettyCashTransactionController: Object.assign(PettyCashTransactionController, PettyCashTransactionController),
    FinanceBankController: Object.assign(FinanceBankController, FinanceBankController),
    BankReconciliationController: Object.assign(BankReconciliationController, BankReconciliationController),
    Accounting: Object.assign(Accounting, Accounting),
    Finance: Object.assign(Finance, Finance),
    ExceptionReportController: Object.assign(ExceptionReportController, ExceptionReportController),
    Monitoring: Object.assign(Monitoring, Monitoring),
    DocumentDownloadController: Object.assign(DocumentDownloadController, DocumentDownloadController),
    ReimbursementController: Object.assign(ReimbursementController, ReimbursementController),
    Procurement: Object.assign(Procurement, Procurement),
    ProjectController: Object.assign(ProjectController, ProjectController),
    ProjectFinanceController: Object.assign(ProjectFinanceController, ProjectFinanceController),
    ProjectGanttController: Object.assign(ProjectGanttController, ProjectGanttController),
    ProjectTaskController: Object.assign(ProjectTaskController, ProjectTaskController),
    ProjectTeamController: Object.assign(ProjectTeamController, ProjectTeamController),
    ProjectMilestoneController: Object.assign(ProjectMilestoneController, ProjectMilestoneController),
    ProjectResourceController: Object.assign(ProjectResourceController, ProjectResourceController),
    ProjectDocumentController: Object.assign(ProjectDocumentController, ProjectDocumentController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers