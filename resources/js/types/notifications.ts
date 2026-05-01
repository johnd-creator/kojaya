export interface Notification {
    id: string
    type: string
    data: NotificationData
    read_at: string | null
    created_at: string
    is_read: boolean
}

export interface NotificationData {
    title?: string
    message?: string
    url?: string
    [key: string]: any
}

export interface NotificationPreference {
    email_enabled: boolean
    database_enabled: boolean
    push_enabled: boolean
    channels: string[]
}

export interface PaginatedNotifications {
    data: Notification[]
    links: {
        first: string | null
        last: string | null
        prev: string | null
        next: string | null
    }
    meta: {
        current_page: number
        from: number | null
        last_page: number
        links: {
            url: string | null
            label: string
            active: boolean
        }[]
        path: string
        per_page: number
        to: number | null
        total: number
    }
}

export type NotificationType =
    | 'App\\Notifications\\ContractExpiring'
    | 'App\\Notifications\\SioExpiring'
    | 'App\\Notifications\\McuDue'
    | 'App\\Notifications\\LeaveApprovalRequired'
    | 'App\\Notifications\\OvertimeApprovalRequired'
    | 'App\\Notifications\\PayrollApprovalRequired'
    | 'App\\Notifications\\InvoicePaymentReminder'
    | 'App\\Notifications\\WorkOrderAssigned'
    | string

export type NotificationFilter = 'all' | 'unread' | 'read'
