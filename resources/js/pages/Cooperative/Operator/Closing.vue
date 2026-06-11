<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDateTime } from "@/lib/formatters";
import {
  show as closingRoute,
  lock,
  unlock,
} from "@/routes/cooperative/operator/closing";
import { complete as completeClosingStep } from "@/routes/cooperative/operator/closing/steps";

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
const selectedPeriod = ref<string | null>(null);
const lockReason = ref("");
const showLockSheet = ref(false);
const flashMessage = ref("");
const flashError = ref(false);

const page = usePage();

function showToast(message: string, error = false) {
  flashMessage.value = message;
  flashError.value = error;
  setTimeout(() => {
    flashMessage.value = "";
  }, 4000);
}

const completedSteps = computed(() =>
  checklist.value.filter((s) => s.completed).length
);

const totalSteps = computed(() => checklist.value.length);

const completionPercent = computed(() =>
  totalSteps.value > 0
    ? Math.round((completedSteps.value / totalSteps.value) * 100)
    : 0
);

async function loadClosing(periodKey: string) {
  if (!periodKey) return;
  loading.value = true;
  try {
    const response = await fetch(closingRoute({ period: periodKey }).url, {
      headers: { Accept: "application/json" },
    });
    const json = await response.json();
    period.value = json.data.period;
    isLocked.value = json.data.is_locked;
    checklist.value = json.data.checklist;
    selectedPeriod.value = periodKey;
  } finally {
    loading.value = false;
  }
}

async function toggleStep(step: ChecklistStep) {
  if (isLocked.value) return;
  if (step.completed) return;

  try {
    const response = await fetch(
      completeClosingStep({ period: period.value }).url,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ step_key: step.step_key }),
      }
    );

    if (response.ok) {
      const json = await response.json();
      const idx = checklist.value.findIndex(
        (s) => s.step_key === step.step_key
      );
      if (idx !== -1) {
        checklist.value[idx] = json.data;
      }
      showToast(`"${step.label}" ditandai selesai.`);
    }
  } catch {
    showToast("Tidak dapat menyelesaikan langkah.", true);
  }
}

async function lockPeriod() {
  if (!lockReason.value.trim()) return;

  try {
    const response = await fetch(lock({ period: period.value }).url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ reason: lockReason.value }),
    });

    if (response.ok) {
      isLocked.value = true;
      showLockSheet.value = false;
      lockReason.value = "";
      showToast(`Periode ${period.value} telah dikunci.`);
    }
  } catch {
    showToast("Tidak dapat mengunci periode.", true);
  }
}

async function unlockPeriod() {
  try {
    const response = await fetch(unlock({ period: period.value }).url, {
      method: "POST",
      headers: { Accept: "application/json" },
    });

    if (response.ok) {
      isLocked.value = false;
      showToast(`Periode ${period.value} telah dibuka kembali.`);
    }
  } catch {
    showToast("Tidak dapat membuka periode.", true);
  }
}
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      { title: 'Dashboard', href: '/' },
      { title: 'Operator Koperasi', href: '/cooperative/operator/dashboard' },
      { title: 'Tutup Periode', href: '/cooperative/operator/closing' },
    ]"
  >
    <div class="p-4 space-y-6">
      <div
        v-if="flashMessage"
        :class="
          flashError
            ? 'bg-destructive/10 border-destructive text-destructive'
            : 'bg-primary/10 border-primary text-primary'
        "
        class="rounded-md border p-3 text-sm"
      >
        {{ flashMessage }}
      </div>

      <h1 class="text-2xl font-bold">Tutup Periode Koperasi</h1>

      <!-- Periode Selector & Progress -->
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
          <Label for="period-select">Periode</Label>
          <Input
            id="period-select"
            v-model="selectedPeriod"
            placeholder="YYYY-MM"
            :disabled="loading"
            class="w-32"
            @keyup.enter="loadClosing(selectedPeriod ?? '')"
          />
          <Button
            variant="outline"
            :disabled="!selectedPeriod || loading"
            @click="loadClosing(selectedPeriod ?? '')"
          >
            Muat
          </Button>
        </div>

        <div v-if="period" class="flex items-center gap-4">
          <Badge :variant="isLocked ? 'destructive' : 'default'">
            {{ isLocked ? "Terkunci" : "Terbuka" }}
          </Badge>
          <div class="text-sm text-muted-foreground">
            Checklist: {{ completedSteps }}/{{ totalSteps }}
            ({{ completionPercent }}%)
          </div>
          <div class="h-2 w-32 rounded-full bg-muted">
            <div
              class="h-2 rounded-full bg-primary transition-all"
              :style="{ width: `${completionPercent}%` }"
            />
          </div>
        </div>
      </div>

      <!-- Checklist -->
      <div v-if="period && checklist.length > 0" class="space-y-2">
        <h2 class="text-lg font-semibold">Checklist Tutup Periode {{ period }}</h2>

        <div
          v-for="step in checklist"
          :key="step.id"
          class="flex items-start gap-3 rounded-lg border p-4 transition"
          :class="step.completed ? 'bg-muted/40' : ''"
        >
          <input
            type="checkbox"
            :checked="step.completed"
            :disabled="isLocked || step.completed"
            class="mt-1 h-4 w-4"
            @change="toggleStep(step)"
          />
          <div class="flex-1 space-y-1">
            <div class="flex items-center gap-2">
              <Badge variant="outline">{{ step.module }}</Badge>
              <span class="font-medium">{{ step.label }}</span>
            </div>
            <div v-if="step.notes" class="text-sm text-muted-foreground">
              {{ step.notes }}
            </div>
            <div
              v-if="step.completed_at"
              class="text-xs text-muted-foreground"
            >
              Selesai {{ formatDateTime(step.completed_at) }}
            </div>
          </div>
        </div>
      </div>

      <div
        v-else-if="period && checklist.length === 0"
        class="rounded-lg border p-10 text-center text-muted-foreground"
      >
        Tidak ada checklist untuk periode ini.
      </div>

      <!-- Lock / Unlock Actions -->
      <div v-if="period" class="flex gap-3">
        <Button
          v-if="!isLocked"
          variant="default"
          :disabled="loading || (completionPercent < 100)"
          @click="showLockSheet = true"
        >
          Kunci Periode
        </Button>
        <Button
          v-else
          variant="outline"
          @click="unlockPeriod"
        >
          Buka Kembali
        </Button>
      </div>

      <!-- Lock Reason Sheet -->
      <div
        v-if="showLockSheet"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="showLockSheet = false"
      >
        <div class="w-full max-w-md rounded-lg bg-background p-6 shadow-xl">
          <h3 class="text-lg font-semibold">Alasan Mengunci Periode</h3>
          <p class="mt-1 text-sm text-muted-foreground">
            Semua checklist harus selesai sebelum periode dikunci.
          </p>
          <div class="mt-4 space-y-2">
            <Label for="lock-reason">Alasan</Label>
            <Textarea
              id="lock-reason"
              v-model="lockReason"
              placeholder="Masukkan alasan mengunci periode..."
            />
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <Button variant="outline" @click="showLockSheet = false">
              Batal
            </Button>
            <Button
              variant="default"
              :disabled="!lockReason.trim()"
              @click="lockPeriod"
            >
              Konfirmasi Kunci
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
