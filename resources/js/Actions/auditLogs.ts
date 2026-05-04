export interface AuditLog {
  id: number;
  user: {
    id: number;
    name: string;
    email: string;
  } | null;
  action: string;
  module: string;
  subject_type: string | null;
  subject_id: number | null;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  updated_at: string;
}

export interface AuditLogFilters {
  user_id?: number;
  module?: string;
  action?: string;
  date_from?: string;
  date_to?: string;
  per_page?: number;
  page?: number;
}

export interface AuditLogResponse {
  data: AuditLog[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const API_BASE = "/api/audit-logs";

const fetchJson = async <T>(url: string, options?: RequestInit): Promise<T> => {
  const response = await fetch(url, {
    headers: {
      Accept: "application/json",
    },
    credentials: "same-origin",
    ...options,
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch: ${response.status}`);
  }

  return response.json();
};

export const auditLogsApi = {
  async list(filters?: AuditLogFilters): Promise<AuditLogResponse> {
    const params = new URLSearchParams();

    if (filters?.user_id) params.append("user_id", filters.user_id.toString());
    if (filters?.module) params.append("module", filters.module);
    if (filters?.action) params.append("action", filters.action);
    if (filters?.date_from) params.append("date_from", filters.date_from);
    if (filters?.date_to) params.append("date_to", filters.date_to);
    if (filters?.per_page)
      params.append("per_page", filters.per_page.toString());
    if (filters?.page) params.append("page", filters.page.toString());

    const queryString = params.toString();
    const url = queryString ? `${API_BASE}?${queryString}` : API_BASE;

    return fetchJson<AuditLogResponse>(url);
  },

  async show(id: number): Promise<AuditLog> {
    return fetchJson<AuditLog>(`${API_BASE}/${id}`);
  },

  async history(subjectType: string, subjectId: number): Promise<AuditLog[]> {
    return fetchJson<AuditLog[]>(
      `${API_BASE}/history/${subjectType}/${subjectId}`,
    );
  },

  async export(
    filters?: AuditLogFilters,
  ): Promise<{ data: AuditLog[]; exported_at: string }> {
    const params = new URLSearchParams();

    if (filters?.user_id) params.append("user_id", filters.user_id.toString());
    if (filters?.module) params.append("module", filters.module);
    if (filters?.action) params.append("action", filters.action);
    if (filters?.date_from) params.append("date_from", filters.date_from);
    if (filters?.date_to) params.append("date_to", filters.date_to);

    const queryString = params.toString();
    const url = queryString
      ? `${API_BASE}/export?${queryString}`
      : `${API_BASE}/export`;

    return fetchJson<{ data: AuditLog[]; exported_at: string }>(url);
  },
};
