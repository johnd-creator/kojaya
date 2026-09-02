import axios from "axios";

export interface EmployeeCertificate {
  id: string | number;
  employee_id: string | number;
  certificate_type: string;
  certificate_number: string;
  issue_date: string;
  expiry_date: string | null;
  issuing_authority: string;
  status: "VALID" | "EXPIRING" | "EXPIRED" | "REVOKED";
  document_path: string | null;
  document_url?: string | null;
  has_document?: boolean;
  document_download_url?: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface CertificateFormData {
  certificate_type: string;
  certificate_number: string;
  issue_date: string;
  expiry_date?: string | null;
  issuing_authority?: string | null;
  notes?: string | null;
  document?: File;
}

export interface PaginatedCertificates {
  data: EmployeeCertificate[];
  links?: Record<string, unknown>;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export async function fetchCertificates(
  employeeId: string | number,
  params?: {
    status?: string;
    page?: number;
    per_page?: number;
  },
): Promise<PaginatedCertificates> {
  const response = await axios.get(
    `/api/employees/${employeeId}/certificates`,
    { params },
  );
  return response.data;
}

export async function createCertificate(
  employeeId: string | number,
  data: CertificateFormData,
): Promise<EmployeeCertificate> {
  const response = await axios.post(
    `/api/employees/${employeeId}/certificates`,
    data,
  );
  return response.data;
}

export async function updateCertificate(
  employeeId: string | number,
  id: string | number,
  data: Partial<CertificateFormData>,
): Promise<EmployeeCertificate> {
  const response = await axios.put(
    `/api/employees/${employeeId}/certificates/${id}`,
    data,
  );
  return response.data;
}

export async function deleteCertificate(
  employeeId: string | number,
  id: string | number,
): Promise<void> {
  await axios.delete(`/api/employees/${employeeId}/certificates/${id}`);
}

export async function uploadCertificateDocument(
  employeeId: string | number,
  certificateId: string | number,
  file: File,
): Promise<{ document_path: string; has_document?: boolean; document_download_url?: string }> {
  const formData = new FormData();
  formData.append("document", file);

  const response = await axios.post(
    `/api/employees/${employeeId}/certificates/${certificateId}/upload`,
    formData,
    {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    },
  );
  return response.data;
}

export async function downloadCertificateDocument(
  employeeId: string | number,
  certificateId: string | number,
): Promise<Blob> {
  const response = await axios.get(
    `/api/employees/${employeeId}/certificates/${certificateId}/document`,
    {
      responseType: "blob",
    },
  );
  return response.data;
}
