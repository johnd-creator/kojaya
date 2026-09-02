import axios from "axios";

export interface MedicalCheckup {
  id: string | number;
  employee_id: string | number;
  checkup_date: string;
  next_checkup_date: string | null;
  result: "FIT" | "FIT_WITH_RESTRICTION" | "UNFIT";
  fit_to_work: boolean;
  notes: string | null;
  document_path: string | null;
  document_url?: string | null;
  has_document?: boolean;
  document_download_url?: string | null;
  doctor_name: string | null;
  clinic_name: string | null;
  created_at: string;
  updated_at: string;
}

export interface McuFormData {
  checkup_date: string;
  next_checkup_date?: string | null;
  result: "FIT" | "FIT_WITH_RESTRICTION" | "UNFIT";
  fit_to_work: boolean;
  notes?: string | null;
  doctor_name?: string | null;
  clinic_name?: string | null;
  document?: File;
}

export interface PaginatedMedicalCheckups {
  data: MedicalCheckup[];
  links?: Record<string, unknown>;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export async function fetchMcuRecords(
  employeeId: string | number,
  params?: {
    status?: string;
    page?: number;
    per_page?: number;
  },
): Promise<PaginatedMedicalCheckups> {
  const response = await axios.get(
    `/api/employees/${employeeId}/mcu`,
    { params },
  );
  return response.data;
}

export async function createMcu(
  employeeId: string | number,
  data: McuFormData,
): Promise<MedicalCheckup> {
  const response = await axios.post(
    `/api/employees/${employeeId}/mcu`,
    data,
  );
  return response.data;
}

export async function updateMcu(
  employeeId: string | number,
  id: string | number,
  data: Partial<McuFormData>,
): Promise<MedicalCheckup> {
  const response = await axios.put(
    `/api/employees/${employeeId}/mcu/${id}`,
    data,
  );
  return response.data;
}

export async function deleteMcu(
  employeeId: string | number,
  id: string | number,
): Promise<void> {
  await axios.delete(`/api/employees/${employeeId}/mcu/${id}`);
}

export async function uploadMcuDocument(
  employeeId: string | number,
  mcuId: string | number,
  file: File,
): Promise<{ document_path: string; has_document?: boolean; document_download_url?: string }> {
  const formData = new FormData();
  formData.append("document", file);

  const response = await axios.post(
    `/api/employees/${employeeId}/mcu/${mcuId}/upload`,
    formData,
    {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    },
  );
  return response.data;
}

export async function downloadMcuDocument(
  employeeId: string | number,
  mcuId: string | number,
): Promise<Blob> {
  const response = await axios.get(
    `/api/employees/${employeeId}/mcu/${mcuId}/document`,
    {
      responseType: "blob",
    },
  );
  return response.data;
}

