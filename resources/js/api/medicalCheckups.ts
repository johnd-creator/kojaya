import axios from "axios";

export interface MedicalCheckup {
  id: number;
  employee_id: number;
  checkup_date: string;
  checkup_type: string;
  clinic_name: string;
  doctor_name: string;
  results: string;
  next_checkup_date: string | null;
  status: "FIT" | "FIT_WITH_RESTRICTION" | "UNFIT";
  notes: string | null;
  documents: string[];
  created_at: string;
  updated_at: string;
}

export interface McuFormData {
  employee_id: number;
  checkup_date: string;
  checkup_type: string;
  clinic_name: string;
  doctor_name: string;
  results: string;
  next_checkup_date?: string;
  notes?: string;
}

export interface PaginatedMedicalCheckups {
  data: MedicalCheckup[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export async function fetchMcuRecords(params?: {
  employee_id?: number;
  status?: string;
  page?: number;
  per_page?: number;
}): Promise<PaginatedMedicalCheckups> {
  const response = await axios.get("/api/mcu-records", { params });
  return response.data;
}

export async function createMcu(data: McuFormData): Promise<MedicalCheckup> {
  const response = await axios.post("/api/mcu-records", data);
  return response.data;
}

export async function updateMcu(
  id: number,
  data: Partial<McuFormData>,
): Promise<MedicalCheckup> {
  const response = await axios.put(`/api/mcu-records/${id}`, data);
  return response.data;
}

export async function deleteMcu(id: number): Promise<void> {
  await axios.delete(`/api/mcu-records/${id}`);
}

export async function fetchUpcomingMcu(
  days?: number,
): Promise<MedicalCheckup[]> {
  const response = await axios.get("/api/mcu-records/upcoming", {
    params: { days },
  });
  return response.data;
}

export async function fetchDueForMcu(months?: number): Promise<
  {
    employee_id: number;
    employee_name: string;
    department: string;
    last_checkup_date: string;
    days_until_due: number;
  }[]
> {
  const response = await axios.get("/api/mcu-records/due", {
    params: { months },
  });
  return response.data;
}
