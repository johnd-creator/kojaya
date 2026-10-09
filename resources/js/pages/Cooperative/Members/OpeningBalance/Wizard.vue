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
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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
import { show as showMemberRoute } from "@/routes/cooperative/members";

type Category = "POKOK" | "WAJIB" | "SUKARELA" | "KHUSUS";

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
  mode: "DIRECT" | "CALCULATED";
  cut_off_date: string | null;
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
  source_types: Record<SourceType, string>;
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

const flash = computed(
  () => usePage().props.flash as { success?: string } | undefined,
);

const form = useForm({
  mode: "DIRECT" as const,
  cut_off_date: props.default_period.end ?? "",
  direct_amounts: { POKOK: 0, WAJIB: 0, SUKARELA: 0, KHUSUS: 0 } as Record<
    Category,
    string | number
  >,
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

const directCategories: Category[] = ["POKOK", "WAJIB", "SUKARELA", "KHUSUS"];
const directTotal = computed(() =>
  directCategories.reduce(
    (sum, category) => sum + (Number(form.direct_amounts[category]) || 0),
    0,
  ),
);
const directErrors = computed(() => Object.values(form.errors));
watch(
  () => form.data(),
  () => {
    preview.value = null;
  },
  { deep: true },
);

const isCalculating = ref(false);
const calculationError = ref<string | null>(null);

async function calculatePreview() {
  isCalculating.value = true;
  calculationError.value = null;
  preview.value = null;

  try {
    const payload = form.data();

    const response = await fetch(previewRoute(props.member.id).url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": (usePage().props.csrf_token as string) ?? "",
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
  form.post(storeRoute(props.member.id).url, {
    onSuccess: () => {
      router.reload({ only: ["history", "flash"] });
      form.reset();
      form.cut_off_date = props.default_period.end ?? "";
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
  router.post(
    postBatchRoute(postingBatchId.value).url,
    {
      confirmation_notes: postNotes.value,
    },
    {
      onSuccess: () => {
        postDialogOpen.value = false;
        postingBatchId.value = null;
        router.reload({ only: ["history", "flash"] });
      },
      onError: () => {
        // keep dialog open
      },
    },
  );
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
  router.post(
    voidBatchRoute(voidBatchId.value).url,
    {
      reason: voidReason.value,
    },
    {
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
    },
  );
}

const canShowWizard = computed(() => props.member.status !== "RESIGNED");

watch(
  () => props.member.id,
  () => {
    form.reset();
    form.cut_off_date = props.default_period.end ?? "";
    preview.value = null;
  },
);
</script>

<template>
  <AppLayout :title="`Wizard Saldo Awal - ${member.nama_anggota}`">
    <Head :title="`Wizard Saldo Awal - ${member.nama_anggota}`" />

    <PageContainer>
        <div
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
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
          <Button variant="outline" as-child>
            <Link :href="showMemberRoute.url(member.id)">
              <ArrowLeft class="mr-2 size-4" />
              Kembali ke Detail Anggota
            </Link>
          </Button>
        </div>

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
            <CardTitle>Saldo Awal Anggota</CardTitle>
            <CardDescription
              >Masukkan saldo akhir hasil rekonsiliasi buku lama per tanggal
              cut-off.</CardDescription
            >
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="max-w-xs">
              <Label for="direct-cutoff">Per tanggal</Label>
              <Input
                id="direct-cutoff"
                v-model="form.cut_off_date"
                type="date"
              />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div v-for="category in directCategories" :key="category">
                <Label :for="`direct-${category}`"
                  >Simpanan {{ category }}</Label
                >
                <Input
                  :id="`direct-${category}`"
                  v-model="form.direct_amounts[category]"
                  type="number"
                  min="0"
                  step="0.01"
                />
              </div>
            </div>
            <p class="text-lg font-semibold">
              Total: {{ formatCurrency(directTotal) }}
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <Label for="direct-source">Sumber</Label>
                <select
                  id="direct-source"
                  v-model="form.source_type"
                  class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                >
                  <option
                    v-for="(label, value) in source_types"
                    :key="value"
                    :value="value"
                  >
                    {{ label }}
                  </option>
                </select>
              </div>
              <div>
                <Label for="direct-reference">Referensi sumber (opsional)</Label
                ><Input id="direct-reference" v-model="form.source_reference" />
              </div>
              <div>
                <Label for="direct-document-date"
                  >Tanggal dokumen (opsional)</Label
                ><Input
                  id="direct-document-date"
                  v-model="form.source_document_date"
                  type="date"
                />
              </div>
              <div>
                <Label for="direct-notes">Catatan</Label
                ><Textarea id="direct-notes" v-model="form.notes" />
              </div>
            </div>
            <div
              v-if="directErrors.length || calculationError"
              role="alert"
              class="text-destructive text-sm"
            >
              <p v-for="error in directErrors" :key="error">{{ error }}</p>
              <p>{{ calculationError }}</p>
            </div>
            <div
              v-if="preview"
              class="space-y-2 rounded-md border p-4"
              aria-live="polite"
            >
              <p class="font-medium">
                Pratinjau per {{ formatDate(preview.calculation_end_period) }}
              </p>
              <p v-for="line in preview.lines" :key="line.category_snapshot">
                {{ line.category_snapshot }}:
                {{ formatCurrency(line.total_amount) }}
              </p>
              <p class="font-semibold">
                Total: {{ formatCurrency(preview.total_amount) }}
              </p>
              <div
                v-if="preview.has_conflicts"
                role="alert"
                class="text-amber-700"
              >
                <p
                  v-for="conflict in preview.conflicts"
                  :key="`${conflict.category}-${conflict.entry_type}-${conflict.period}`"
                >
                  {{ conflict.message }}
                </p>
              </div>
            </div>
            <div class="flex gap-2">
              <Button
                variant="outline"
                :disabled="
                  isCalculating || directTotal <= 0 || !form.cut_off_date
                "
                @click="calculatePreview"
                >{{ isCalculating ? "Memeriksa..." : "Preview" }}</Button
              >
              <Button
                :disabled="!preview || form.processing"
                @click="submitDraft"
                >{{ form.processing ? "Menyimpan..." : "Simpan Draft" }}</Button
              >
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
                  <div
                    class="flex flex-wrap items-center justify-between gap-2"
                  >
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
                    <template v-if="batch.mode === 'DIRECT'"
                      >Saldo langsung per
                      {{ formatDate(batch.cut_off_date) }}</template
                    ><template v-else
                      >{{ batch.months_count }} bulan &middot;
                      {{ formatDate(batch.period_start ?? "") }} &ndash;
                      {{ formatDate(batch.period_end ?? "") }}</template
                    >
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
                  <div v-if="batch.posted_at" class="text-emerald-600 text-xs">
                    Diposting pada {{ formatDate(batch.posted_at) }}
                  </div>
                  <div v-if="batch.voided_at" class="text-rose-600 text-xs">
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
            Silakan aktifkan kembali anggota ini untuk melanjutkan wizard saldo
            awal.
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
          <p v-if="voidError" class="text-xs text-rose-600">
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
