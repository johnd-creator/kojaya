<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import {
  Bell,
  BellOff,
  CheckCheck,
  CheckCircle2,
  CircleDot,
  Mail,
  ReceiptText,
  WalletCards,
} from "lucide-vue-next";
import type { LucideIcon } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDateTime } from "@/lib/formatters";

type NotificationItem = {
  id: string;
  type: string;
  read_at?: string | null;
  created_at: string;
  data?: { title?: string; message?: string } | null;
};

defineProps<{
  notifications: {
    data: NotificationItem[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
  };
}>();

const notificationIcon = (type: string): LucideIcon => {
  const lower = type.toLowerCase();
  if (
    lower.includes("payment") ||
    lower.includes("invoice") ||
    lower.includes("dues")
  ) {
    return ReceiptText;
  }

  if (
    lower.includes("loan") ||
    lower.includes("savings") ||
    lower.includes("simpanan")
  ) {
    return WalletCards;
  }

  if (lower.includes("email") || lower.includes("mail")) {
    return Mail;
  }

  return Bell;
};

const formatTimeAgo = (iso: string): string => {
  const date = new Date(iso);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMin = Math.floor(diffMs / 60000);
  const diffHr = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHr / 24);

  if (diffMin < 1) {
    return "Baru saja";
  }

  if (diffMin < 60) {
    return `${diffMin} menit lalu`;
  }

  if (diffHr < 24) {
    return `${diffHr} jam lalu`;
  }

  if (diffDay < 7) {
    return `${diffDay} hari lalu`;
  }

  return formatDateTime(iso);
};

const markAsRead = (id: string): void => {
  router.patch(
    `/api/notifications/${id}/read`,
    {},
    { preserveScroll: true, only: [] },
  );
  router.reload({ preserveScroll: true });
};

const markAllAsRead = (): void => {
  router.post("/api/notifications/mark-all-read", {}, { preserveScroll: true });
  router.reload({ preserveScroll: true });
};
</script>

<template>
  <Head title="Notifikasi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Notifikasi', href: '/member/notifications' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-3 sm:gap-5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/20 sm:h-16 sm:w-16"
          >
            <Bell class="h-6 w-6 sm:h-8 sm:w-8" />
          </div>
          <div>
            <h1
              class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl"
            >
              Notifikasi
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Pantau pemberitahuan penting terkait keanggotaan, simpanan, dan
              pinjaman Anda.
            </p>
          </div>
        </header>

        <section
          class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm sm:p-5"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 shadow-sm"
            >
              <CircleDot class="h-5 w-5" />
            </div>
            <div>
              <p class="font-bold tracking-tight text-zinc-900 dark:text-white">
                Kotak Masuk
              </p>
              <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Total {{ notifications.total }} notifikasi
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl px-4 font-bold text-xs uppercase tracking-wider"
            @click="markAllAsRead"
          >
            <CheckCheck class="h-4 w-4" />
            Tandai Semua Dibaca
          </Button>
        </section>

        <section
          v-if="notifications.data.length > 0"
          class="flex flex-col gap-3"
        >
          <div
            v-for="notification in notifications.data"
            :key="notification.id"
            class="group flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:gap-4 sm:p-5"
            :class="
              notification.read_at
                ? 'border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900'
                : 'border-blue-200 dark:border-blue-900/30 bg-blue-50/30 dark:bg-blue-950/10'
            "
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm"
              :class="
                notification.read_at
                  ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400'
                  : 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'
              "
            >
              <component
                :is="notificationIcon(notification.type)"
                class="h-5 w-5"
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-bold text-zinc-900 dark:text-white">
                    {{ notification.data?.title || "Notifikasi" }}
                  </p>
                  <p
                    class="mt-1 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400"
                  >
                    {{
                      notification.data?.message || "Tidak ada detail tambahan."
                    }}
                  </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2">
                  <span
                    class="text-xs font-medium text-zinc-500 dark:text-zinc-400"
                    >{{ formatTimeAgo(notification.created_at) }}</span
                  >
                  <span
                    v-if="!notification.read_at"
                    class="flex items-center gap-1 rounded-full bg-blue-100 dark:bg-blue-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-400"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Baru
                  </span>
                </div>
              </div>
              <div
                class="mt-2 text-xs"
                :class="
                  notification.read_at
                    ? 'text-zinc-500 dark:text-zinc-400'
                    : 'text-zinc-500 dark:text-zinc-400'
                "
              >
                {{ formatDateTime(notification.created_at) }}
                <span v-if="notification.read_at" class="ml-2"
                  >· Dibaca {{ formatTimeAgo(notification.read_at) }}</span
                >
              </div>
            </div>
            <Button
              v-if="!notification.read_at"
              variant="ghost"
              size="sm"
              class="shrink-0 rounded-lg text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/30"
              @click="markAsRead(notification.id)"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              Tandai Dibaca
            </Button>
          </div>
        </section>

        <section
          v-else
          class="flex flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 py-16 text-center"
        >
          <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400"
          >
            <BellOff class="h-8 w-8" />
          </div>
          <p class="text-base font-bold text-zinc-700 dark:text-zinc-200">
            Belum ada notifikasi
          </p>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Notifikasi terkait keanggotaan dan transaksi Anda akan muncul di
            sini.
          </p>
        </section>

        <div
          v-if="notifications.last_page > 1"
          class="flex items-center justify-center gap-3"
        >
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl px-4"
            :disabled="!notifications.prev_page_url"
            @click="router.get(notifications.prev_page_url ?? '')"
          >
            Sebelumnya
          </Button>
          <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">
            Halaman {{ notifications.current_page }} dari
            {{ notifications.last_page }}
          </span>
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl px-4"
            :disabled="!notifications.next_page_url"
            @click="router.get(notifications.next_page_url ?? '')"
          >
            Selanjutnya
          </Button>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
