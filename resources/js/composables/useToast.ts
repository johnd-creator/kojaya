import { ref, type Ref } from "vue";

export interface Toast {
  id: string;
  title: string;
  description?: string;
  variant?: "success" | "error" | "info" | "warning" | "status";
}

const toasts = ref<Toast[]>([]);
let counter = 0;
let recentDedupe: { key: string; at: number }[] = [];

export function useToast() {
  function toast(toast: Omit<Toast, "id">) {
    const key = `${toast.variant ?? "info"}:${toast.title}:${toast.description ?? ""}`;
    const now = Date.now();
    recentDedupe = recentDedupe.filter((d) => now - d.at < 1500);

    if (recentDedupe.some((d) => d.key === key)) {
      return;
    }

    recentDedupe.push({ key, at: now });

    const id = `toast-${++counter}`;
    toasts.value = [...toasts.value, { ...toast, id }];

    setTimeout(() => {
      dismiss(id);
    }, 5000);
  }

  function dismiss(id: string) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }

  return { toasts, toast, dismiss };
}
