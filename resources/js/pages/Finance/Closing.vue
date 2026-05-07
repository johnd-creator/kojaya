<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDateTime } from "@/lib/formatters";

interface ChecklistStep {
  id: number;
  period: string;
  module: string;
  step_key: string;
  label: string;
  completed: boolean;
  completed_by_id: number | null;
  completed_at: string | null;
  notes: string | null;
}

interface ClosingData {
  period: string;
  is_locked: boolean;
  checklist: ChecklistStep[];
}

const props = defineProps<{
  closing?: ClosingData;
}>();

const period = ref(props.closing?.period ?? "");
const isLocked = ref(props.closing?.is_locked ?? false);
const checklist = ref<ChecklistStep[]>(props.closing?.checklist ?? []);
const loading = ref(false);
const lockReason = ref("");
const showLockSheet = ref(false);
const flashMessage = ref("");
const flashError = ref(false);

const page = usePage();

async function loadPeriod() {
  if (!period.value) return;
  loading.value = true;
  try {
    const res = await fetch(`/finance/closing/${period.value}`);
    const json = await res.json();
    isLocked.value = json.data.is_locked;
    checklist.value = json.data.checklist.map((s: any) => ({
      ...s,
      completed: s.status === "DONE",
    }));
    flashMessage.value = "";
  } catch {
    flashMessage.value = "Gagal memuat data periode";
    flashError.value = true;
  } finally {
    loading.value = false;
  }
}

async function toggleStep(step: ChecklistStep) {
  if (step.completed) return;
  const res = await fetch(`/finance/closing/${period.value}/steps`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": page.props.csrf_token as string },
    body: JSON.stringify({ step_key: step.step_key }),
  });
  if (res.ok) {
    const json = await res.json();
    const idx = checklist.value.findIndex((s) => s.id === step.id);
    if (idx !== -1) {
      checklist.value[idx] = { ...json.data, completed: json.data.status === "DONE" };
    }
    flashMessage.value = "Langkah berhasil diselesaikan";
    flashError.value = false;
  }
}

async function confirmLock() {
  if (!lockReason.value.trim()) return;
  const res = await fetch(`/finance/closing/${period.value}/lock`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": page.props.csrf_token as string },
    body: JSON.stringify({ reason: lockReason.value }),
  });
  if (res.ok) {
    isLocked.value = true;
    showLockSheet.value = false;
    lockReason.value = "";
    flashMessage.value = "Periode berhasil dikunci";
    flashError.value = false;
  } else {
    const json = await res.json();
    flashMessage.value = json.message ?? "Gagal mengunci periode";
    flashError.value = true;
  }
}

async function confirmUnlock() {
  const res = await fetch(`/finance/closing/${period.value}/unlock`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": page.props.csrf_token as string },
  });
  if (res.ok) {
    isLocked.value = false;
    flashMessage.value = "Periode berhasil dibuka";
    flashError.value = false;
  }
}

const allDone = computed(() => checklist.value.every((s) => s.completed));
const progressPct = computed(() => {
  if (!checklist.value.length) return 0;
  return Math.round((checklist.value.filter((s) => s.completed).length / checklist.value.length) * 100);
});
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Tutup Periode Keuangan</h1>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
        Checklist dan lock periode untuk modul Finance
      </p>

      <div v-if="flashMessage" class="mt-4 p-3 rounded-lg text-sm font-medium" :class="flashError ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'">
        {{ flashMessage }}
      </div>

      <div class="mt-6 flex items-end gap-4">
        <div class="flex-1 max-w-xs">
          <Label for="period-input">Periode (YYYY-MM)</Label>
          <Input id="period-input" v-model="period" placeholder="2026-01" class="mt-1" />
        </div>
        <Button @click="loadPeriod" :disabled="loading || !period.trim()">
          {{ loading ? "Memuat..." : "Muat Periode" }}
        </Button>
      </div>

      <div v-if="checklist.length" class="mt-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
              Progress: {{ progressPct }}% ({{ checklist.filter(s => s.completed).length }}/{{ checklist.length }} langkah)
            </span>
            <Badge :variant="isLocked ? 'destructive' : 'secondary'" class="ml-2">
              {{ isLocked ? "TERKUNCI" : "TERBUKA" }}
            </Badge>
          </div>
        </div>

        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
          <div class="bg-blue-500 h-3 rounded-full transition-all duration-300" :style="{ width: progressPct + '%' }"></div>
        </div>

        <div v-for="step in checklist" :key="step.id" class="flex items-start gap-4 p-4 bg-white dark:bg-zinc-900 shadow rounded-lg border border-zinc-200 dark:border-zinc-800">
          <input type="checkbox" :checked="step.completed" :disabled="step.completed || isLocked" @change="toggleStep(step)" class="mt-1 h-5 w-5 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
          <div class="flex-1">
            <p class="font-medium text-zinc-900 dark:text-white" :class="{ 'line-through text-zinc-400': step.completed }">
              {{ step.label }}
            </p>
            <p v-if="step.completed_at" class="text-xs text-zinc-500 mt-1">
              Selesai: {{ formatDateTime(step.completed_at) }}
            </p>
            <p v-if="step.notes" class="text-xs text-zinc-400 mt-1">
              Catatan: {{ step.notes }}
            </p>
          </div>
        </div>

        <div class="flex gap-4 mt-6">
          <Button v-if="!isLocked && allDone" @click="showLockSheet = true">
            Kunci Periode
          </Button>
          <Button v-if="isLocked" variant="destructive" @click="confirmUnlock">
            Buka Kunci
          </Button>
        </div>
      </div>

      <div v-else-if="!loading && period" class="mt-6 text-sm text-zinc-500">
        Tidak ada checklist untuk periode ini. Masukkan periode valid dan klik "Muat Periode".
      </div>

      <div v-if="showLockSheet" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-xl p-6 w-full max-w-md">
          <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Kunci Periode {{ period }}</h2>
          <p class="text-sm text-zinc-500 mt-1">Semua langkah checklist sudah selesai. Masukkan alasan untuk mengunci periode.</p>
          <div class="mt-4">
            <Label for="lock-reason">Alasan</Label>
            <Textarea id="lock-reason" v-model="lockReason" placeholder="Masukkan alasan kunci periode..." class="mt-1" rows="3" />
          </div>
          <div class="flex gap-3 mt-6 justify-end">
            <Button variant="outline" @click="showLockSheet = false">Batal</Button>
            <Button @click="confirmLock" :disabled="!lockReason.trim()">Kunci</Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
