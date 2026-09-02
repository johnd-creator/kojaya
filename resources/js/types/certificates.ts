export type CertificateType = "SIO_K3" | "TRAINING" | "OTHER";
export type CertificateStatus = "VALID" | "EXPIRED" | "REVOKED";

export interface EmployeeCertificate {
  id: string;
  employee_id: string;
  certificate_type: CertificateType;
  certificate_number: string;
  issue_date: string;
  expiry_date: string | null;
  issuing_authority: string | null;
  document_path: string | null;
  document_url?: string | null;
  has_document?: boolean;
  document_download_url?: string | null;
  status: CertificateStatus;
  notes: string | null;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
}

export interface CertificateFormData {
  certificate_type: CertificateType;
  certificate_number: string;
  issue_date: string;
  expiry_date: string | null;
  issuing_authority: string | null;
  notes?: string;
  document?: File;
}

export interface PaginatedCertificates {
  data: EmployeeCertificate[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: {
      url: string | null;
      label: string;
      active: boolean;
    }[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}

export interface CertificateComplianceReport {
  total_employees: number;
  compliant_employees: number;
  non_compliant_employees: number;
  compliance_rate: number;
  certificates: EmployeeCertificate[];
}

export interface UploadedFile {
  name: string;
  size: number;
  type: string;
  url?: string;
  file?: File;
}
