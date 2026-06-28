export interface Notification {
  id: string;
  type: string;
  notification_type?: string;
  event_type: string;
  category: string;
  severity: "info" | "success" | "warning" | "critical" | string;
  title: string;
  message: string;
  subject?: {
    type: string | null;
    id: string | number | null;
    label: string | null;
  };
  actor?: {
    id: string | number;
    name: string;
  } | null;
  action?: {
    label: string;
    url: string | null;
  };
  metadata?: Record<string, any>;
  data: NotificationData;
  read_at: string | null;
  created_at: string;
  is_read: boolean;
}

export interface NotificationData {
  title?: string;
  message?: string;
  url?: string;
  [key: string]: any;
}

export interface NotificationPreference {
  email_enabled: boolean;
  database_enabled: boolean;
  push_enabled: boolean;
  whatsapp_enabled?: boolean;
  whatsapp_phone?: string | null;
  channels: string[];
  categories?: Record<string, string[]>;
}

export interface NotificationSummary {
  unread_count: number;
  by_category: Record<string, number>;
  by_severity: Record<string, number>;
}

export interface PaginatedNotifications {
  data: Notification[];
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

export type NotificationType =
  | "App\\Notifications\\ContractExpiring"
  | "App\\Notifications\\SioExpiring"
  | "App\\Notifications\\McuDue"
  | "App\\Notifications\\LeaveApprovalRequired"
  | "App\\Notifications\\OvertimeApprovalRequired"
  | "App\\Notifications\\PayrollApprovalRequired"
  | "App\\Notifications\\InvoicePaymentReminder"
  | "App\\Notifications\\WorkOrderAssigned"
  | string;

export type NotificationFilter = "all" | "unread" | "read";
