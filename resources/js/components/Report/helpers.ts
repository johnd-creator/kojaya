import axios from "axios";
import {
  payslip,
  payrollSummary,
  payrollDetail,
  attendanceReport,
  leaveReport,
  certificateCompliance,
  mcuCompliance,
} from "@/actions/App/Http/Controllers/ReportController";

export interface ReportFilter {
  period?: string;
  date_from?: string;
  date_to?: string;
  employee_id?: number;
  organization_id?: number;
  unit_id?: number;
  format?: "pdf" | "excel";
}

export interface Report {
  id: string;
  name: string;
  description: string;
  category: string;
  formats: ("pdf" | "excel")[];
  filters: (
    | "period"
    | "date_from"
    | "employee_id"
    | "organization_id"
    | "unit_id"
  )[];
}

export const downloadBlob = (blob: Blob, filename: string) => {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

export const generatePayslip = async (
  employeeId: number,
  period: string,
  format: "pdf" | "excel" = "pdf",
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: payslip.url({ project: projectId, employeeId, period }),
    responseType: "blob",
    params: { format },
  });
  return response.data;
};

export const generatePayrollSummary = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: payrollSummary.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const generatePayrollDetail = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: payrollDetail.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const generateAttendanceReport = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: attendanceReport.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const generateLeaveReport = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: leaveReport.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const generateCertificateCompliance = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: certificateCompliance.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const generateMcuCompliance = async (
  filters: ReportFilter & { format?: "pdf" | "excel" },
): Promise<Blob> => {
  const projectId = 1;
  const response = await axios({
    method: "get",
    url: mcuCompliance.url({ project: projectId }),
    responseType: "blob",
    params: filters,
  });
  return response.data;
};

export const reports: Report[] = [
  {
    id: "payslip",
    name: "Payslip",
    description: "Generate individual employee payslip for a specific period",
    category: "payroll",
    formats: ["pdf"],
    filters: ["employee_id", "period"],
  },
  {
    id: "payroll-summary",
    name: "Payroll Summary",
    description: "Summary of payroll data for all employees",
    category: "payroll",
    formats: ["pdf", "excel"],
    filters: ["period", "organization_id"],
  },
  {
    id: "payroll-detail",
    name: "Payroll Detail",
    description: "Detailed payroll breakdown with all components",
    category: "payroll",
    formats: ["pdf", "excel"],
    filters: ["period", "organization_id"],
  },
  {
    id: "attendance",
    name: "Attendance Report",
    description: "Employee attendance records within a date range",
    category: "attendance",
    formats: ["pdf", "excel"],
    filters: ["date_from", "date_to", "organization_id", "unit_id"],
  },
  {
    id: "leave",
    name: "Leave Report",
    description: "Employee leave records and balances",
    category: "leave",
    formats: ["pdf", "excel"],
    filters: ["date_from", "date_to", "organization_id", "unit_id"],
  },
  {
    id: "certificate-compliance",
    name: "Certificate Compliance",
    description: "Employee certification compliance status",
    category: "compliance",
    formats: ["pdf", "excel"],
    filters: ["date_from", "date_to", "organization_id"],
  },
  {
    id: "mcu-compliance",
    name: "MCU Compliance",
    description: "Medical Check-Up compliance status",
    category: "compliance",
    formats: ["pdf", "excel"],
    filters: ["date_from", "date_to", "organization_id"],
  },
];

export const availableReports = reports;
