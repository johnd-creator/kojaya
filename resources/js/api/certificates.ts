import axios from 'axios'

export interface EmployeeCertificate {
  id: number
  employee_id: number
  certificate_type: string
  certificate_number: string
  issue_date: string
  expiry_date: string | null
  issuing_authority: string
  status: 'VALID' | 'EXPIRING' | 'EXPIRED'
  document_path: string | null
  notes: string | null
  created_at: string
  updated_at: string
}

export interface CertificateFormData {
  employee_id: number
  certificate_type: string
  certificate_number: string
  issue_date: string
  expiry_date?: string
  issuing_authority: string
  notes?: string
}

export interface PaginatedCertificates {
  data: EmployeeCertificate[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export async function fetchCertificates(params?: {
  employee_id?: number
  status?: string
  page?: number
  per_page?: number
}): Promise<PaginatedCertificates> {
  const response = await axios.get('/api/certificates', { params })
  return response.data
}

export async function createCertificate(data: CertificateFormData): Promise<EmployeeCertificate> {
  const response = await axios.post('/api/certificates', data)
  return response.data
}

export async function updateCertificate(
  id: number,
  data: Partial<CertificateFormData>
): Promise<EmployeeCertificate> {
  const response = await axios.put(`/api/certificates/${id}`, data)
  return response.data
}

export async function deleteCertificate(id: number): Promise<void> {
  await axios.delete(`/api/certificates/${id}`)
}

export async function uploadCertificateDocument(
  certificateId: number,
  file: File
): Promise<{ document_path: string }> {
  const formData = new FormData()
  formData.append('document', file)

  const response = await axios.post(`/api/certificates/${certificateId}/upload`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return response.data
}
