<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import {
  AlertTriangle,
  ArrowLeft,
  ArrowRight,
  BadgeCheck,
  Calculator,
  CheckCircle2,
  ClipboardList,
  History,
  Info,
  PiggyBank,
  Save,
  Send,
  ShieldAlert,
  Trash2,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";

import EmptyState from "@/components/EmptyState.vue";
import PageContainer from "@/components/PageContainer.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useCan } from "@/composables/useCan";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import {
  post as postBatchRoute,
  voidMethod as voidBatchRoute,
} from "@/routes/cooperative/opening-balances";
import {
  show as showRoute,
  preview as previewRoute,
  store as storeRoute,
} from "@/routes/cooperative/members/opening-balance";

type Category =
  | "POKOK"
  | "WAJIB"
  | "SUKARELA"
  | "KHUSUS";

type SourceType =
  | "MIGRATION_LEDGER"
  | "MANUAL_RECONCILIATION"
  | "EXCEL_IMPORT"
  | "BOARD_DECISION";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

type ContributionType = {
  id: number;
  code: string;
  name: string;
  category: Category;
  default_amount: number;
  frequency: string;
};

type HistoryLine = {
  id: number;
  category: string;
  contribution_type?: string;
  months_count: number;
  unit_amount: number;
  total_amount: number;
  calculation_method: string;
  override_reason: string | null;
};

type HistoryBatch = {
  id: number;
  status: "DRAFT" | "POSTED" | "VOID";
  status_label: string;
  status_tone: Tone;
  total_amount: number;
  months_count: number;
  period_start: string | null;
  period_end: string | null;
  source_type: string | null;
  source_reference: string | null;
  source_document_date: string | null;
  notes: string | null;
  posted_at: string | null;
  posted_by: number | null;
  voided_at: string | null;
  void_reason: string | null;
  lines: HistoryLine[];
};

const props = defineProps<{
  member: {
    id: number;
    no_anggota: string | null;
    nama_anggota: string;
    tanggal_aktif: string | null;
    status: string;
    organization_id: number | null;
    organization_name: string | null;
  };
  contribution_types: ContributionType[];
  source_types: SourceType[];
  history: HistoryBatch[];
  capabilities: {
    can_post: boolean;
    can_void: boolean;
  };
  default_period: {
    start: string | null;
    end: string | null;
  };
}>();

const { can } = useCan();
const canManage = computed(() => can("manage_cooperative_opening_balance"));

const flash = computed(() => usePage().props.flash as { success?: string } | undefined);

const step = ref<number>(1);
const steps = [
  { id: 1, title: "Periode", description: "Pilih periode perhitungan" },
  { id: 2, title: "Kategori", description: "Pilih kategori simpanan" },
  { id: 3, title: "Override", description: "Penyesuaian tarif (opsional)" },
  { id: 4, title: "Sumber", description: "Dokumen sumber & catatan" },
  { id: 5, title: "Pratinjau", description: "Verifikasi hasil kalkulasi" },
];

const form = useForm({
  calculation_start_period: props.default_period.start ?? "",
  calculation_end_period: props.default_period.end ?? "",
  include_current_month: false,
  contribution_types: [] as number[],
  overrides: {} as Record<string, { unit_amount?: number | null; reason?: string | null }>,
  source_type: "MIGRATION_LEDGER" as SourceType,
  source_reference: "",
  source_document_date: "",
  notes: "",
});

const preview = ref<{
  months_count: number;
  total_amount: number;
  calculation_end_period: string;
  calculation_start_period: string;
  lines: {
    contribution_type_id: number;
    contribution_type_name: string;
    category_snapshot: string;
    months_count: number;
    unit_amount: number;
    total_amount: number;
    calculation_method: string;
    override_reason: string | null;
    period_start: string | null;
    period_end: string | null;
  }[];
  conflicts: Array<{
    category: string;
    entry_type: string;
    period: string | null;
    posted_at: string | null;
    amount: number;
    description: string | null;
    overlaps_calculation_period: boolean;
    overlap_month_label: string | null;
    message: string;
  }>;
  has_conflicts: boolean;
} | null>(null);

const isCalculating = ref(false);
const calculationError = ref<string | null>(null);

const groupedContributionTypes = computed(() => {
  const groups: Record<string, ContributionType[]> = {};
  for (const ct of props.contribution_types) {
    if (!groups[ct.category]) groups[ct.category] = [];
    groups[ct.category].push(ct);
  }
  return groups;
});

const selectedContributionDetails = computed(() =>
  props.contribution_types.filter((ct) =>
    form.contribution_types.includes(ct.id),
  ),
);

const overrideEntries = computed(() =>
  selectedContributionDetails.value
    .map((ct) => {
      const value = form.overrides[ct.id];
      return value && (value.unit_amount !== undefined || value.reason)
        ? { contribution: ct, value }
        : null;
    })
    .filter((entry): entry is { contribution: ContributionType; value: { unit_amount?: number | null; reason?: string | null } } => entry !== null),
);

const stepValid = computed(() => {
  if (step.value === 1) {
    return (
      !!form.calculation_start_period &&
      !!form.calculation_end_period &&
      form.calculation_end_period >= form.calculation_start_period
    );
  }
  if (step.value === 2) {
    return form.contribution_types.length > 0;
  }
  if (step.value === 3) {
    return overrideEntries.value.every((entry) => {
      if (entry.value.unit_amount === undefined || entry.value.unit_amount === null) {
        return true;
      }
      if (!entry.value.reason || entry.value.reason.trim().length < 5) {
        return false;
      }
      return true;
    });
  }
  if (step.value === 4) {
    return !!form.source_type;
  }
  return true;
});

function nextStep() {
  if (step.value < steps.length && stepValid.value) {
    step.value += 1;
    if (step.value === 5) {
      void calculatePreview();
    }
  }
}

function previousStep() {
  if (step.value > 1) step.value -= 1;
}

function toggleContributionType(id: number) {
  if (form.contribution_types.includes(id)) {
    form.contribution_types = form.contribution_types.filter((x) => x !== id);
    delete form.overrides[id];
  } else {
    form.contribution_types = [...form.contribution_types, id];
  }
}

function setOverrideReason(id: number, reason: string) {
  form.overrides = {
    ...form.overrides,
    [id]: { ...(form.overrides[id] ?? {}), reason },
  };
}

function setOverrideAmount(id: number, amount: number | null) {
  form.overrides = {
    ...form.overrides,
    [id]: { ...(form.overrides[id] ?? {}), unit_amount: amount ?? null },
  };
}

async function calculatePreview() {
  isCalculating.value = true;
  calculationError.value = null;
  preview.value = null;

  try {
    const payload = {
      ...form.data(),
      overrides: Object.fromEntries(
        overrideEntries.value.map((entry) => [
          entry.contribution.id,
          {
            unit_amount: entry.value.unit_amount ?? null,
            reason: entry.value.reason ?? null,
          },
        ]),
      ),
    };

    const response = await fetch(previewRoute(props.member.id).url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN":
          (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
            ?.content ?? "",
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      calculationError.value =
        data?.message ??
        "Gagal menghitung pratinjau saldo awal. Periksa input Anda.";
      return;
    }

    const data = await response.json();
    preview.value = data.preview;
  } catch (error) {
    calculationError.value = "Terjadi kesalahan saat menghubungi server.";
  } finally {
    isCalculating.value = false;
  }
}

function submitDraft() {
  form.transform((data) => ({
    ...data,
    overrides: Object.fromEntries(
      overrideEntries.value.map((entry) => [
        entry.contribution.id,
        {
          unit_amount: entry.value.unit_amount ?? null,
          reason: entry.value.reason ?? null,
        },
      ]),
    ),
  })).post(storeRoute(props.member.id).url, {
    onSuccess: () => {
      router.reload({ only: ["history", "flash"] });
      step.value = 1;
      form.reset();
      form.calculation_start_period = props.default_period.start ?? "";
      form.calculation_end_period = props.default_period.end ?? "";
    },
  });
}

const postDialogOpen = ref(false);
const postNotes = ref("");
const postingBatchId = ref<number | null>(null);

function openPostDialog(batchId: number) {
  postingBatchId.value = batchId;
  postNotes.value = "";
  postDialogOpen.value = true;
}

function confirmPost() {
  if (!postingBatchId.value) return;
  router.post(postBatchRoute(postingBatchId.value).url, {
    confirmation_notes: postNotes.value,
  }, {
    onSuccess: () => {
      postDialogOpen.value = false;
      postingBatchId.value = null;
      router.reload({ only: ["history", "flash"] });
    },
    onError: () => {
      // keep dialog open
    },
  });
}

const voidDialogOpen = ref(false);
const voidBatchId = ref<number | null>(null);
const voidReason = ref("");
const voidError = ref<string | null>(null);

function openVoidDialog(batchId: number) {
  voidBatchId.value = batchId;
  voidReason.value = "";
  voidError.value = null;
  voidDialogOpen.value = true;
}

function confirmVoid() {
  if (!voidBatchId.value || voidReason.value.trim().length < 5) {
    voidError.value = "Alasan void minimal 5 karakter.";
    return;
  }
  voidError.value = null;
  router.post(voidBatchRoute(voidBatchId.value).url, {
    reason: voidReason.value,
  }, {
    onSuccess: () => {
      voidDialogOpen.value = false;
      voidBatchId.value = null;
      voidReason.value = "";
      router.reload({ only: ["history", "flash"] });
    },
    onError: (errors) => {
      voidError.value =
        errors?.reason ?? "Gagal melakukan void. Periksa kembali input Anda.";
    },
  });
}

const canShowWizard = computed(() => props.member.status !== "RESIGNED");

watch(
  () => props.member.id,
  () => {
    step.value = 1;
    form.reset();
    form.calculation_start_period = props.default_period.start ?? "";
    form.calculation_end_period = props.default_period.end ?? "";
    preview.value = null;
  },
);
</script>

<template>
  <AppLayout
    :title="`Wizard Saldo Awal - ${member.nama_anggota}`"
  >
    <Head :title="`Wizard Saldo Awal - ${member.nama_anggota}`" />

    <PageContainer>
      <template #header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" as-child>
              <Link :href="`/cooperative/members/${member.id}`">
                <ArrowLeft class="size-4" />
              </Link>
            </Button>
            <div>
              <h1 class="text-2xl font-semibold tracking-tight">
                Wizard Saldo Awal
              </h1>
              <p class="text-muted-foreground text-sm">
                {{ member.no_anggota }} &middot; {{ member.nama_anggota }}
                <span v-if="member.organization_name">
                  &middot; {{ member.organization_name }}
                </span>
              </p>
            </div>
          </div>
          <Link :href="`/cooperative/members/${member.id}`">
            <Button variant="outline">
              <ArrowLeft class="mr-2 size-4" />
              Kembali ke Detail Anggota
            </Button>
          </Link>
        </div>
      </template>

      <div
        v-if="flash?.success"
        class="mb-4 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200"
      >
        <CheckCircle2 class="size-4" />
        {{ flash.success }}
      </div>

      <Card
        v-if="member.status === 'RESIGNED'"
        class="mb-6 border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/30"
      >
        <CardContent class="flex items-start gap-3 py-4">
          <AlertTriangle class="mt-0.5 size-5 text-rose-600" />
          <div>
            <p class="font-medium text-rose-700 dark:text-rose-200">
              Wizard saldo awal tidak tersedia
            </p>
            <p class="text-sm text-rose-600 dark:text-rose-300">
              Anggota sudah RESIGNED. Hubungi pengurus untuk reaktivasi sebelum
              memproses saldo awal.
            </p>
          </div>
        </CardContent>
      </Card>

      <div v-if="canShowWizard && canManage" class="grid gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calculator class="size-5" />
              Hitung Saldo Awal
            </CardTitle>
            <CardDescription>
              Wizard multi-langkah untuk menghitung dan menyimpan draft saldo
              awal simpanan anggota lama.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <ol class="grid gap-2 sm:grid-cols-5">
              <li
                v-for="s in steps"
                :key="s.id"
                class="flex flex-col rounded-md border px-3 py-2 text-xs"
                :class="step >= s.id
                  ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-200'
                  : 'border-muted text-muted-foreground'"
              >
                <span class="font-semibold">Langkah {{ s.id }}</span>
                <span class="text-[11px] uppercase tracking-wide">
                  {{ s.title }}
                </span>
              </li>
            </ol>

            <section v-if="step === 1" class="space-y-4">
              <div>
                <Label for="start">Periode Awal Perhitungan</Label>
                <Input
                  id="start"
                  v-model="form.calculation_start_period"
                  type="date"
                />
                <p
                  v-if="form.errors.calculation_start_period"
                  class="mt-1 text-xs text-rose-600"
                >
                  {{ form.errors.calculation_start_period }}
                </p>
              </div>
              <div>
                <Label for="end">Periode Akhir Perhitungan</Label>
                <Input
                  id="end"
                  v-model="form.calculation_end_period"
                  type="date"
                />
                <p
                  v-if="form.errors.calculation_end_period"
                  class="mt-1 text-xs text-rose-600"
                >
                  {{ form.errors.calculation_end_period }}
                </p>
              </div>
              <div class="flex items-start gap-2 rounded-md border p-3">
                <Checkbox
                  id="include-current"
                  :model-value="form.include_current_month"
                  @update:model-value="(value: boolean) => (form.include_current_month = !!value)"
                />
                <div>
                  <Label for="include-current">
                    Sertakan bulan berjalan
                  </Label>
                  <p class="text-muted-foreground text-xs">
                    Otomatis menambahkan bulan saat ini ke akhir periode.
                  </p>
                </div>
              </div>
            </section>

            <section v-else-if="step === 2" class="space-y-4">
              <p class="text-muted-foreground text-sm">
                Pilih satu atau lebih kategori simpanan. Tarif default diambil
                dari master jenis simpanan.
              </p>
              <div
                v-for="(group, category) in groupedContributionTypes"
                :key="category"
                class="rounded-md border p-4"
              >
                <div class="mb-3 flex items-center justify-between">
                  <p class="font-semibold">{{ category }}</p>
                  <Badge variant="outline">
                    {{ group.length }} jenis
                  </Badge>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                  <label
                    v-for="ct in group"
                    :key="ct.id"
                    class="flex cursor-pointer items-start gap-3 rounded-md border p-3 transition hover:border-emerald-300"
                    :class="form.contribution_types.includes(ct.id)
                      ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/30'
                      : ''"
                  >
                    <Checkbox
                      :model-value="form.contribution_types.includes(ct.id)"
                      @update:model-value="toggleContributionType(ct.id)"
                    />
                    <div class="flex-1">
                      <p class="text-sm font-medium">{{ ct.name }}</p>
                      <p class="text-muted-foreground text-xs">
                        {{ ct.code }} &middot; Tarif
                        {{ formatCurrency(ct.default_amount) }} &middot;
                        {{ ct.frequency }}
                      </p>
                    </div>
                  </label>
                </div>
              </div>
              <p
                v-if="form.errors.contribution_types"
                class="text-xs text-rose-600"
              >
                {{ form.errors.contribution_types }}
              </p>
            </section>

            <section v-else-if="step === 3" class="space-y-4">
              <p class="text-muted-foreground text-sm">
                Kosongkan bila tarif sesuai default. Override WAJIB menyertakan
                alasan audit minimal 5 karakter.
              </p>
              <div
                v-if="selectedContributionDetails.length === 0"
                class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
              >
                Tidak ada kategori yang dipilih.
              </div>
              <div
                v-for="ct in selectedContributionDetails"
                :key="ct.id"
                class="grid gap-3 rounded-md border p-4 md:grid-cols-3"
              >
                <div class="md:col-span-1">
                  <p class="font-medium">{{ ct.name }}</p>
                  <p class="text-muted-foreground text-xs">
                    Default: {{ formatCurrency(ct.default_amount) }}
                  </p>
                </div>
                <div>
                  <Label :for="`amount-${ct.id}`">Tarif override (Rp)</Label>
                  <Input
                    :id="`amount-${ct.id}`"
                    type="number"
                    min="0"
                    step="1000"
                    :model-value="form.overrides[ct.id]?.unit_amount ?? ''"
                    @update:model-value="(value) => setOverrideAmount(ct.id, value === '' ? null : Number(value))"
                  />
                </div>
                <div>
                  <Label :for="`reason-${ct.id}`">Alasan override</Label>
                  <Input
                    :id="`reason-${ct.id}`"
                    :model-value="form.overrides[ct.id]?.reason ?? ''"
                    @update:model-value="(value) => setOverrideReason(ct.id, value)"
                  />
                  <p
                    v-if="form.overrides[ct.id]?.unit_amount !== undefined && form.overrides[ct.id]?.unit_amount !== null && (!form.overrides[ct.id]?.reason || ((form.overrides[ct.id]?.reason?.length ?? 0) < 5))"
                    class="mt-1 text-xs text-rose-600"
                  >
                    Alasan wajib diisi minimal 5 karakter bila mengisi tarif override.
                  </p>
                </div>
              </div>
            </section>

            <section v-else-if="step === 4" class="space-y-4">
              <div>
                <Label for="source-type">Tipe Sumber</Label>
                <select
                  id="source-type"
                  v-model="form.source_type"
                  class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                >
                  <option
                    v-for="src in source_types"
                    :key="src"
                    :value="src"
                  >
                    {{ src }}
                  </option>
                </select>
                <p
                  v-if="form.errors.source_type"
                  class="mt-1 text-xs text-rose-600"
                >
                  {{ form.errors.source_type }}
                </p>
              </div>
              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <Label for="source-ref">Referensi Dokumen</Label>
                  <Input
                    id="source-ref"
                    v-model="form.source_reference"
                    placeholder="mis. REF-MIG-001"
                  />
                </div>
                <div>
                  <Label for="source-date">Tanggal Dokumen</Label>
                  <Input
                    id="source-date"
                    v-model="form.source_document_date"
                    type="date"
                  />
                </div>
              </div>
              <div>
                <Label for="notes">Catatan</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  rows="3"
                  placeholder="Catatan audit atau konteks tambahan"
                />
              </div>
            </section>

            <section v-else-if="step === 5" class="space-y-4">
              <div
                v-if="isCalculating"
                class="flex items-center gap-2 text-sm text-muted-foreground"
              >
                <Calculator class="size-4 animate-pulse" />
                Menghitung pratinjau...
              </div>
              <div
                v-else-if="calculationError"
                class="flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200"
              >
                <AlertTriangle class="mt-0.5 size-4" />
                {{ calculationError }}
              </div>
              <div v-else-if="preview" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3">
                  <div class="rounded-md border p-3">
                    <p class="text-muted-foreground text-xs">Total bulan</p>
                    <p class="text-lg font-semibold">
                      {{ preview.months_count }}
                    </p>
                  </div>
                  <div class="rounded-md border p-3">
                    <p class="text-muted-foreground text-xs">Periode</p>
                    <p class="text-sm font-medium">
                      {{ formatDate(preview.calculation_start_period) }}
                      &ndash;
                      {{ formatDate(preview.calculation_end_period) }}
                    </p>
                  </div>
                  <div class="rounded-md border p-3">
                    <p class="text-muted-foreground text-xs">Total saldo</p>
                    <p class="text-lg font-semibold text-emerald-600">
                      {{ formatCurrency(preview.total_amount) }}
                    </p>
                  </div>
                </div>
                <div
                  v-if="preview.has_conflicts"
                  class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900"
                  role="alert"
                  data-test="opening-balance-conflicts"
                >
                  <div class="flex items-start gap-3">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="20"
                      height="20"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="mt-0.5 size-5 shrink-0"
                      aria-hidden="true"
                    >
                      <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                      <line x1="12" y1="9" x2="12" y2="13" />
                      <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <div class="flex-1 space-y-2">
                      <p class="font-semibold">
                        Perhatian: ditemukan {{ preview.conflicts.length }} mutasi
                        simpanan yang berpotensi tumpang tindih.
                      </p>
                      <p class="text-xs">
                        Tinjau daftar berikut. Jika mutasi di bawah ini memang
                        sahih (misalnya pembayaran di luar periode kalkulasi),
                        Anda boleh melanjutkan. Jika tidak, kosongkan baris
                        kontribusi terkait atau batalkan wizard.
                      </p>
                      <ul class="space-y-1.5 text-xs">
                        <li
                          v-for="(conflict, idx) in preview.conflicts"
                          :key="`${conflict.category}-${conflict.entry_type}-${conflict.period ?? 'na'}-${idx}`"
                          class="rounded border border-amber-200/80 bg-white/60 px-2 py-1.5"
                        >
                          <p class="font-medium">
                            {{ conflict.category }} &mdash;
                            {{ conflict.entry_type }}
                            <span
                              v-if="conflict.overlaps_calculation_period"
                              class="ml-1 inline-flex items-center rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-rose-700"
                            >
                              tumpang tindih
                            </span>
                          </p>
                          <p class="text-amber-800">
                            {{ conflict.message }}
                          </p>
                          <p
                            v-if="conflict.period"
                            class="text-[11px] text-amber-700"
                          >
                            Periode {{ conflict.period }} &middot;
                            Nominal {{ formatCurrency(conflict.amount) }}
                            <span v-if="conflict.posted_at">
                              &middot; diposting
                              {{ formatDate(conflict.posted_at) }}
                            </span>
                          </p>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="overflow-x-auto rounded-md border">
                  <table class="w-full text-sm">
                    <thead class="bg-muted text-left">
                      <tr>
                        <th class="px-3 py-2 font-medium">Kategori</th>
                        <th class="px-3 py-2 font-medium">Jenis</th>
                        <th class="px-3 py-2 text-right font-medium">Bulan</th>
                        <th class="px-3 py-2 text-right font-medium">Tarif</th>
                        <th class="px-3 py-2 text-right font-medium">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="line in preview.lines"
                        :key="line.contribution_type_id"
                        class="border-t"
                      >
                        <td class="px-3 py-2">
                          <Badge variant="outline">
                            {{ line.category_snapshot }}
                          </Badge>
                        </td>
                        <td class="px-3 py-2">
                          <p class="font-medium">
                            {{ line.contribution_type_name }}
                          </p>
                          <p class="text-muted-foreground text-xs">
                            {{ line.calculation_method }}
                          </p>
                          <p
                            v-if="line.override_reason"
                            class="text-rose-600 text-xs"
                          >
                            Override: {{ line.override_reason }}
                          </p>
                        </td>
                        <td class="px-3 py-2 text-right">
                          {{ line.months_count }}
                        </td>
                        <td class="px-3 py-2 text-right">
                          {{ formatCurrency(line.unit_amount) }}
                        </td>
                        <td class="px-3 py-2 text-right font-medium">
                          {{ formatCurrency(line.total_amount) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div
                v-else
                class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
              >
                <Info class="mr-2 inline size-4" />
                Klik tombol Hitung Ulang untuk menampilkan pratinjau.
              </div>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex gap-2">
                <Button
                  variant="outline"
                  :disabled="step === 1"
                  @click="previousStep"
                >
                  <ArrowLeft class="mr-2 size-4" />
                  Kembali
                </Button>
              </div>
              <div class="flex gap-2">
                <Button
                  v-if="step < 5"
                  :disabled="!stepValid"
                  @click="nextStep"
                >
                  Lanjut
                  <ArrowRight class="ml-2 size-4" />
                </Button>
                <template v-else>
                  <Button
                    variant="outline"
                    :disabled="isCalculating"
                    @click="calculatePreview"
                  >
                    <Calculator class="mr-2 size-4" />
                    Hitung Ulang
                  </Button>
                  <Button
                    :disabled="!preview || form.processing"
                    @click="submitDraft"
                  >
                    <Save class="mr-2 size-4" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Draft" }}
                  </Button>
                </template>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <History class="size-5" />
              Riwayat Batch
            </CardTitle>
            <CardDescription>
              Daftar batch saldo awal anggota ini.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <EmptyState
              v-if="history.length === 0"
              title="Belum ada batch"
              description="Belum ada saldo awal yang pernah disimpan untuk anggota ini."
            />
            <div v-else class="space-y-3">
              <Card
                v-for="batch in history"
                :key="batch.id"
                class="border-muted"
              >
                <CardHeader class="pb-2">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <StatusPill
                      :label="batch.status_label"
                      :tone="batch.status_tone"
                    />
                    <span class="text-muted-foreground text-xs">
                      #{{ batch.id }} &middot; {{ batch.source_type ?? "-" }}
                    </span>
                  </div>
                  <CardTitle class="text-base">
                    {{ formatCurrency(batch.total_amount) }}
                  </CardTitle>
                  <CardDescription>
                    {{ batch.months_count }} bulan &middot;
                    {{ formatDate(batch.period_start ?? "") }} &ndash;
                    {{ formatDate(batch.period_end ?? "") }}
                  </CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                  <ul class="text-muted-foreground space-y-1 text-xs">
                    <li
                      v-for="line in batch.lines"
                      :key="line.id"
                      class="flex items-center justify-between"
                    >
                      <span>
                        {{ line.contribution_type ?? line.category }}
                        ({{ line.category }})
                      </span>
                      <span class="font-medium">
                        {{ formatCurrency(line.total_amount) }}
                      </span>
                    </li>
                  </ul>
                  <div
                    v-if="batch.posted_at"
                    class="text-emerald-600 text-xs"
                  >
                    Diposting pada {{ formatDate(batch.posted_at) }}
                  </div>
                  <div
                    v-if="batch.voided_at"
                    class="text-rose-600 text-xs"
                  >
                    Di-void: {{ batch.void_reason }}
                  </div>
                  <div class="flex flex-wrap gap-2 pt-2">
                    <Button
                      v-if="batch.status === 'DRAFT' && capabilities.can_post"
                      size="sm"
                      @click="openPostDialog(batch.id)"
                    >
                      <Send class="mr-2 size-4" />
                      Posting ke Ledger
                    </Button>
                    <Button
                      v-if="batch.status === 'POSTED' && capabilities.can_void"
                      size="sm"
                      variant="outline"
                      class="text-rose-600 border-rose-200 hover:bg-rose-50"
                      @click="openVoidDialog(batch.id)"
                    >
                      <Trash2 class="mr-2 size-4" />
                      Void
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-else-if="!canManage" class="border-amber-200 bg-amber-50">
        <CardContent class="flex items-start gap-3 py-4">
          <ShieldAlert class="mt-0.5 size-5 text-amber-600" />
          <div>
            <p class="font-medium text-amber-700">Akses ditolak</p>
            <p class="text-sm text-amber-600">
              Anda tidak memiliki izin untuk mengelola saldo awal anggota.
            </p>
          </div>
        </CardContent>
      </Card>

      <Card v-else>
        <CardContent class="flex items-start gap-3 py-4">
          <ClipboardList class="mt-0.5 size-5 text-muted-foreground" />
          <p class="text-sm text-muted-foreground">
            Silakan aktifkan kembali anggota ini untuk melanjutkan wizard saldo awal.
          </p>
        </CardContent>
      </Card>

      <Dialog v-model:open="postDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Konfirmasi Posting Saldo Awal</DialogTitle>
            <DialogDescription>
              Setelah diposting, batch ini akan otomatis tercatat ke ledger
              simpanan (entry_type = OPENING_BALANCE). Hanya pengurus dengan
              permission approve yang dapat melanjutkan.
            </DialogDescription>
          </DialogHeader>
          <Label for="post-notes">Catatan (opsional)</Label>
          <Textarea
            id="post-notes"
            v-model="postNotes"
            rows="3"
            placeholder="Catatan tambahan untuk audit"
          />
          <DialogFooter>
            <Button variant="outline" @click="postDialogOpen = false">
              Batal
            </Button>
            <Button @click="confirmPost">
              <BadgeCheck class="mr-2 size-4" />
              Posting Sekarang
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog v-model:open="voidDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Void Saldo Awal</DialogTitle>
            <DialogDescription>
              Void akan membuat entry OPENING_BALANCE_REVERSAL di ledger
              sehingga saldo simpanan kembali ke posisi sebelum batch ini.
              Tindakan ini memerlukan permission khusus dan tidak dapat
              dibatalkan.
            </DialogDescription>
          </DialogHeader>
          <Label for="void-reason">Alasan void</Label>
          <Textarea
            id="void-reason"
            v-model="voidReason"
            rows="3"
            placeholder="Mis. Salah periode awal, koreksi nominal, dsb."
          />
          <p
            v-if="voidError"
            class="text-xs text-rose-600"
          >
            {{ voidError }}
          </p>
          <DialogFooter>
            <Button variant="outline" @click="voidDialogOpen = false">
              Batal
            </Button>
            <Button
              variant="destructive"
              :disabled="voidReason.trim().length < 5"
              @click="confirmVoid"
            >
              <Trash2 class="mr-2 size-4" />
              Konfirmasi Void
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </PageContainer>
  </AppLayout>
</template>
