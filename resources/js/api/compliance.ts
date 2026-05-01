import axios from 'axios'

export interface ComplianceSummary {
  total_employees: number
  compliant_employees: number
  non_compliant_employees: number
  certificate_compliance: {
    total: number
    valid: number
    expiring: number
    expired: number
  }
  mcu_compliance: {
    total: number
    fit: number
    fit_with_restriction: number
    unfit: number
    overdue: number
  }
}

export interface NonCompliantEmployee {
  employee_id: number
  employee_name: string
  department: string
  position: string
  issues: {
    type: 'certificate' | 'mcu'
    description: string
    severity: 'high' | 'medium' | 'low'
  }[]
}

export async function fetchComplianceSummary(): Promise<ComplianceSummary> {
  const response = await axios.get('/api/compliance/summary')
  return response.data
}

export async function fetchNonCompliantEmployees(params?: {
  department?: string
  severity?: string
  type?: string
}): Promise<NonCompliantEmployee[]> {
  const response = await axios.get('/api/compliance/non-compliant', { params })
  return response.data
}

export async function exportComplianceReport(params?: {
  department?: string
  format?: 'pdf' | 'excel'
}): Promise<Blob> {
  const response = await axios.get('/api/compliance/export', {
    params,
    responseType: 'blob'
  })
  return response.data
}

export async function fetchCertificateCompliance(params?: {
  department?: string
}): Promise<{
  compliant: number
  non_compliant: number
  details: {
    employee_id: number
    employee_name: string
    certificates: {
      type: string
      status: string
      expiry_date: string
    }[]
  }[]
}> {
  const response = await axios.get('/api/compliance/certificates', { params })
  return response.data
}

export async function fetchMcuCompliance(params?: {
  department?: string
}): Promise<{
  compliant: number
  non_compliant: number
  details: {
    employee_id: number
    employee_name: string
    last_checkup: string
    next_checkup: string
    status: string
  }[]
}> {
  const response = await axios.get('/api/compliance/mcu', { params })
  return response.data
}
