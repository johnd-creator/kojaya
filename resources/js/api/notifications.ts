import axios from "axios";
import type {
  Notification,
  NotificationPreference,
  NotificationSummary,
  PaginatedNotifications,
} from "@/types/notifications";

export async function fetchNotifications(params?: {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
  status?: "read" | "unread";
  category?: string;
  severity?: string;
}): Promise<PaginatedNotifications> {
  const response = await axios.get("/api/notifications", { params });
  return response.data;
}

export async function fetchRecentNotifications(limit = 5): Promise<{
  data: Notification[];
  meta: { limit: number; unread_count: number };
}> {
  const response = await axios.get("/api/notifications/recent", {
    params: { limit },
  });
  return response.data;
}

export async function fetchNotificationSummary(): Promise<NotificationSummary> {
  const response = await axios.get("/api/notifications/summary");
  return response.data;
}

export async function fetchUnreadCount(): Promise<number> {
  const response = await axios.get("/api/notifications/unread-count");
  return response.data.count;
}

export async function markAsRead(notificationId: string): Promise<void> {
  await axios.patch(`/api/notifications/${notificationId}/read`);
}

export async function markAllAsRead(): Promise<void> {
  await axios.post("/api/notifications/mark-all-read");
}

export async function deleteNotification(
  notificationId: number,
): Promise<void> {
  await axios.delete(`/api/notifications/${notificationId}`);
}

export async function fetchNotificationPreferences(): Promise<NotificationPreference> {
  const response = await axios.get("/api/notifications/preferences");
  return response.data;
}

export async function updateNotificationPreferences(
  preferences: Partial<NotificationPreference>,
): Promise<NotificationPreference> {
  const response = await axios.put(
    "/api/notifications/preferences",
    preferences,
  );
  return response.data;
}
