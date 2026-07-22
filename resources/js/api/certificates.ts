import axios from "axios";
import type {
  CertificateFormData,
  EmployeeCertificate,
  PaginatedCertificates,
} from "@/types/certificates";

export type { CertificateFormData, EmployeeCertificate, PaginatedCertificates };

export async function fetchCertificates(
  employeeId: string | number,
  params?: { status?: string; page?: number; per_page?: number },
): Promise<PaginatedCertificates> {
  const response = await axios.get(`/api/employees/${employeeId}/certificates`, {
    params,
  });
  return response.data;
}

export async function createCertificate(
  employeeId: string | number,
  data: CertificateFormData,
): Promise<EmployeeCertificate> {
  const response = await axios.post(`/api/employees/${employeeId}/certificates`, data);
  return response.data;
}

export async function updateCertificate(
  employeeId: string | number,
  id: string | number,
  data: Partial<CertificateFormData>,
): Promise<EmployeeCertificate> {
  const response = await axios.put(`/api/employees/${employeeId}/certificates/${id}`, data);
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
): Promise<{ document_path: string }> {
  const formData = new FormData();
  formData.append("document", file);

  const response = await axios.post(
    `/api/employees/${employeeId}/certificates/${certificateId}/upload`,
    formData,
    { headers: { "Content-Type": "multipart/form-data" } },
  );
  return response.data;
}
