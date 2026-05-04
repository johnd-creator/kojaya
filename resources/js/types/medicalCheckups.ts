export type McuResult = "FIT" | "FIT_WITH_RESTRICTION" | "UNFIT";

export interface MedicalCheckup {
  id: string;
  employee_id: string;
  checkup_date: string;
  next_checkup_date: string | null;
  result: McuResult;
  fit_to_work: boolean;
  notes: string | null;
  document_path: string | null;
  doctor_name: string | null;
  clinic_name: string | null;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
}

export interface McuFormData {
  checkup_date: string;
  next_checkup_date?: string;
  result: McuResult;
  fit_to_work: boolean;
  notes?: string;
  doctor_name?: string;
  clinic_name?: string;
  document?: File;
}

export interface PaginatedMedicalCheckups {
  data: MedicalCheckup[];
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

export interface McuComplianceReport {
  total_employees: number;
  compliant_employees: number;
  non_compliant_employees: number;
  compliance_rate: number;
  medical_checkups: MedicalCheckup[];
}
