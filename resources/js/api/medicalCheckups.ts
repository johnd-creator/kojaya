import axios from "axios";
import type {
  McuFormData,
  MedicalCheckup,
  PaginatedMedicalCheckups,
} from "@/types/medicalCheckups";

export type { McuFormData, MedicalCheckup, PaginatedMedicalCheckups };

export async function fetchMcuRecords(
  employeeId: string | number,
  params?: { status?: string; page?: number; per_page?: number },
): Promise<PaginatedMedicalCheckups> {
  const response = await axios.get(`/api/employees/${employeeId}/mcu`, {
    params,
  });
  return response.data;
}

export async function createMcu(
  employeeId: string | number,
  data: McuFormData,
): Promise<MedicalCheckup> {
  const response = await axios.post(`/api/employees/${employeeId}/mcu`, data);
  return response.data;
}

export async function updateMcu(
  employeeId: string | number,
  id: string | number,
  data: Partial<McuFormData>,
): Promise<MedicalCheckup> {
  const response = await axios.put(`/api/employees/${employeeId}/mcu/${id}`, data);
  return response.data;
}

export async function deleteMcu(
  employeeId: string | number,
  id: string | number,
): Promise<void> {
  await axios.delete(`/api/employees/${employeeId}/mcu/${id}`);
}

export async function fetchUpcomingMcu(days?: number): Promise<MedicalCheckup[]> {
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
