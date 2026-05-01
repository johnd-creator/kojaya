import axios from 'axios'

export interface Notification {
  id: number
  type: string
  notifiable_id: number
  notifiable_type: string
  data: Record<string, any>
  read_at: string | null
  created_at: string
  formatted_date: string
  relative_time: string
}

export interface NotificationPreference {
  id: number
  user_id: number
  email_enabled: boolean
  push_enabled: boolean
  in_app_enabled: boolean
  notification_types: string[]
}

export interface PaginatedNotifications {
  data: Notification[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export async function fetchNotifications(params?: {
  page?: number
  per_page?: number
  unread_only?: boolean
}): Promise<PaginatedNotifications> {
  const response = await axios.get('/api/notifications', { params })
  return response.data
}

export async function fetchUnreadCount(): Promise<number> {
  const response = await axios.get('/api/notifications/unread-count')
  return response.data.count
}

export async function markAsRead(notificationId: number): Promise<void> {
  await axios.post(`/api/notifications/${notificationId}/read`)
}

export async function markAllAsRead(): Promise<void> {
  await axios.post('/api/notifications/mark-all-read')
}

export async function deleteNotification(notificationId: number): Promise<void> {
  await axios.delete(`/api/notifications/${notificationId}`)
}

export async function fetchNotificationPreferences(): Promise<NotificationPreference> {
  const response = await axios.get('/api/notifications/preferences')
  return response.data
}

export async function updateNotificationPreferences(
  preferences: Partial<NotificationPreference>
): Promise<NotificationPreference> {
  const response = await axios.put('/api/notifications/preferences', preferences)
  return response.data
}
