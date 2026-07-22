import axios from "axios";
import type {
  CertificateComplianceReport,
  EmployeeCertificate,
  McuComplianceReport,
  MedicalCheckup,
} from "@/types";

export interface ComplianceSummary {
  total_employees: number;
  compliant_employees: number;
  non_compliant_employees: number;
  certificate_compliance: {
    total: number;
    valid: number;
    expiring: number;
    expired: number;
  };
  mcu_compliance: {
    total: number;
    fit: number;
    fit_with_restriction: number;
    unfit: number;
    overdue: number;
  };
}

export interface NonCompliantEmployee {
  id: number;
  first_name: string;
  last_name: string;
  employee_code: string;
  valid_certificates: number;
  next_mcu_date: string | null;
}

export async function fetchComplianceSummary(): Promise<ComplianceSummary> {
  const response = await axios.get("/api/compliance/summary");
  return response.data;
}

export async function fetchNonCompliantEmployees(params?: {
  department?: string;
  severity?: string;
  type?: string;
}): Promise<{ data: NonCompliantEmployee[]; total: number }> {
  const response = await axios.get("/api/reports/non-compliant-employees", { params });
  const page = response.data.data ?? response.data;
  return { data: page.data ?? [], total: page.total ?? page.data?.length ?? 0 };
}

export async function exportComplianceReport(
  typeOrParams:
    | "certificate"
    | "mcu"
    | { department?: string; format?: "pdf" | "excel" } = {},
): Promise<Blob> {
  const params =
    typeof typeOrParams === "string" ? { type: typeOrParams } : typeOrParams;
  const response = await axios.get("/api/compliance/export", {
    params,
    responseType: "blob",
  });
  return response.data;
}

export async function fetchCertificateCompliance(params?: {
  department?: string;
  organization_id?: string;
}): Promise<CertificateComplianceReport> {
  const response = await axios.get("/api/reports/certificate-compliance", { params });
  const summary = response.data.summary;
  return {
    total_employees: summary.total,
    compliant_employees: summary.valid,
    non_compliant_employees: summary.total - summary.valid,
    compliance_rate: summary.compliance_rate,
    certificates: (response.data.expiring_soon?.data ??
      response.data.expiring_soon ?? []) as EmployeeCertificate[],
  };
}

export async function fetchMcuCompliance(params?: {
  department?: string;
  organization_id?: string;
}): Promise<McuComplianceReport> {
  const response = await axios.get("/api/reports/mcu-compliance", { params });
  const summary = response.data.summary;
  return {
    total_employees: summary.total,
    compliant_employees: summary.up_to_date,
    non_compliant_employees: summary.total - summary.up_to_date,
    compliance_rate: summary.compliance_rate,
    medical_checkups: (response.data.due_soon?.data ??
      response.data.due_soon ?? []) as MedicalCheckup[],
  };
}
