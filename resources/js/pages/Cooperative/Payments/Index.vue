<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
  Check,
  CheckCircle2,
  CheckCheck,
  ImagePlus,
  Layers,
  PiggyBank,
  ReceiptText,
  Search,
  Sparkles,
  WalletCards,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import BulkActionBar from "@/components/ui/data-table/BulkActionBar.vue";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { approve, bulkApprove, index, store } from "@/routes/cooperative/payments";

type MemberOption = {
  id: number;
  member_no: string;
  name: string;
};

type ContributionTypeOption = {
  id: number;
  code: string;
  name: string;
  category: string;
  default_amount: number | string;
};

const props = defineProps<{
  payments: any;
  members: MemberOption[];
  contributionTypes: ContributionTypeOption[];
  filters: any;
  canApprovePayments: boolean;
}>();

const form = useForm({
  cooperative_member_id: "",
  cooperative_contribution_type_id: "",
  amount: "",
  payment_method: "CASH",
  paid_at: new Date().toISOString().slice(0, 10),
  notes: "",
  proof: null as File | null,
  status: "APPROVED",
});

const memberSearch = ref("");
const memberSearchFocused = ref(false);

const selectedMember = computed(() =>
  props.members.find(
    (member) => String(member.id) === String(form.cooperative_member_id),
  ),
);

const filteredMembers = computed(() => {
  const keyword = memberSearch.value.trim().toLowerCase();
  if (!keyword) return [];
  return props.members
    .filter((member) => {
      const haystack = `${member.name} ${member.member_no}`.toLowerCase();
      return haystack.includes(keyword);
    })
    .slice(0, 8);
});

const showMemberResults = computed(
  () =>
    memberSearchFocused.value &&
    !selectedMember.value &&
    memberSearch.value.trim().length > 0,
);

const selectedContributionType = computed(() =>
  props.contributionTypes.find(
    (type) => String(type.id) === String(form.cooperative_contribution_type_id),
  ),
);

const isFixedAmount = computed(
  () => selectedContributionType.value?.code === "POKOK",
);

const amountHelper = computed(() => {
  if (!selectedContributionType.value) {
    return "Pilih jenis simpanan untuk melihat aturan nominal.";
  }
  if (selectedContributionType.value.code === "POKOK") {
    return "Simpanan Pokok ditetapkan Rp 200.000 per anggota.";
  }
  return "Simpanan Sukarela bebas diisi sesuai nominal setoran anggota.";
});

const proofFileName = computed(() => form.proof?.name ?? "");

watch(selectedMember, (member) => {
  if (member) {
    memberSearch.value = `${member.name} (${member.member_no})`;
  }
});

watch(
  selectedContributionType,
  (type, previousType) => {
    if (!type) {
      form.amount = "";
      return;
    }
    const defaultAmount = String(Number(type.default_amount ?? 0));
    if (type.code === "SUKARELA") {
      if (previousType?.code === "POKOK") {
        form.amount = "";
      }
      return;
    }
    form.amount = defaultAmount;
  },
  { immediate: true },
);

const selectMember = (member: MemberOption) => {
  form.cooperative_member_id = String(member.id);
  memberSearch.value = `${member.name} (${member.member_no})`;
  memberSearchFocused.value = false;
};

const clearSelectedMember = () => {
  form.cooperative_member_id = "";
  memberSearch.value = "";
  memberSearchFocused.value = false;
};

const closeMemberResults = () => {
  window.setTimeout(() => {
    memberSearchFocused.value = false;
  }, 150);
};

const handleProofChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  form.proof = target.files?.[0] ?? null;
};

const submit = () =>
  form.post(store().url, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      form.reset(
        "cooperative_member_id",
        "cooperative_contribution_type_id",
        "amount",
        "notes",
        "proof",
      );
      memberSearch.value = "";
    },
  });

const approvingPaymentId = ref<number | null>(null);
const selectedPayments = ref<any[]>([]);
const showBulkConfirm = ref(false);
const pendingBulkAction = ref<{ action: string; selected: any[] } | null>(null);

const sortField = ref<string>(props.filters?.sort_field ?? "");
const sortDirection = ref<"asc" | "desc">(props.filters?.sort_direction ?? "asc");

const handleSort = (field: string, dir: "asc" | "desc") => {
  sortField.value = field;
  sortDirection.value = dir;
  router.get(index().url, { ...props.filters, sort_field: field, sort_direction: dir }, { preserveState: true, preserveScroll: true });
};

const handleBulkAction = (action: string, selected: any[]) => {
  pendingBulkAction.value = { action, selected };
  showBulkConfirm.value = true;
};

const confirmBulkAction = () => {
  if (!pendingBulkAction.value) return;
  const { action, selected } = pendingBulkAction.value;

  if (action === "approve") {
    const ids = selected.map((p: any) => p.id);
    router.post(
      bulkApprove().url,
      { ids },
      {
        preserveScroll: true,
        onSuccess: () => {
          selectedPayments.value = [];
          showBulkConfirm.value = false;
          pendingBulkAction.value = null;
        },
      },
    );
  }
};

const approvePayment = (payment: { id: number }) => {
  approvingPaymentId.value = payment.id;

  router.post(
    approve(payment.id).url,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        approvingPaymentId.value = null;
      },
    },
  );
};

const columns = computed(() => [
  { header: "Tanggal", key: "paid_at", slot: "paid_at", sortable: true, sortKey: "paid_at" },
  { header: "Anggota", key: "member.name", slot: "member" },
  { header: "Jenis Simpanan", key: "contribution_type.name", slot: "type" },
  { header: "Metode", key: "payment_method", slot: "method" },
  { header: "Status", key: "status", slot: "status", sortable: true, sortKey: "status" },
  { header: "Nominal", key: "amount", slot: "amount", align: "right" as const, sortable: true, sortKey: "amount" },
  { header: "Keterangan", key: "notes", slot: "notes" },
  ...(props.canApprovePayments
    ? [{ header: "Aksi", key: "actions", slot: "actions", align: "right" as const }]
    : []),
]);
</script>

<template>
  <Head title="Pembayaran Koperasi" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pembayaran', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="pointer-events-none absolute -bottom-24 -left-12 size-64 rounded-full bg-sky-300/15 blur-3xl dark:bg-sky-500/10"
          aria-hidden="true"
        />
        <div
          class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <Sparkles class="size-3.5" />
              Transaksi Simpanan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Pembayaran Koperasi
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Catat setoran simpanan anggota, pilih jenis simpanan, upload
              bukti, dan lihat riwayat pembayaran.
            </p>
          </div>

          <div class="grid min-w-0 shrink-0 grid-cols-2 gap-3 sm:max-w-sm">
            <div
              class="rounded-xl border border-white/60 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-emerald-800/40 dark:bg-emerald-950/30"
            >
              <div
                class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
              >
                Total Pembayaran
              </div>
              <div
                class="mt-1 text-lg font-bold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ payments.total ?? 0 }}
              </div>
              <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                Seluruh transaksi
              </div>
            </div>
            <div
              class="rounded-xl border border-white/60 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-sky-800/40 dark:bg-sky-950/30"
            >
              <div
                class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
              >
                Hari Ini
              </div>
              <div
                class="mt-1 text-lg font-bold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ payments.data?.length ?? 0 }}
              </div>
              <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                Data ditampilkan
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader
            title="Catat Pembayaran"
            description="Cari anggota, pilih jenis simpanan, lalu simpan."
            :icon="PiggyBank"
            tone="emerald"
          />
          <CardContent class="space-y-5 px-5 pb-5">
            <form class="space-y-5" @submit.prevent="submit">
              <div class="space-y-2">
                <Label for="member-search">Anggota</Label>
                <div class="relative">
                  <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                  />
                  <Input
                    id="member-search"
                    v-model="memberSearch"
                    class="pl-9"
                    placeholder="Ketik nama atau nomor anggota"
                    autocomplete="off"
                    @focus="memberSearchFocused = true"
                    @input="form.cooperative_member_id = ''"
                    @blur="closeMemberResults"
                  />

                  <div
                    v-if="showMemberResults"
                    class="absolute left-0 right-0 top-full z-30 mt-2 max-h-72 overflow-y-auto rounded-lg border border-zinc-200/80 bg-white p-1 shadow-lg shadow-zinc-950/10 dark:border-zinc-800/80 dark:bg-zinc-900"
                  >
                    <button
                      v-for="member in filteredMembers"
                      :key="member.id"
                      type="button"
                      class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                      @mousedown.prevent="selectMember(member)"
                    >
                      <div>
                        <div
                          class="font-medium text-zinc-950 dark:text-zinc-50"
                        >
                          {{ member.name }}
                        </div>
                        <div class="text-xs text-zinc-500">
                          No. Anggota {{ member.member_no }}
                        </div>
                      </div>
                      <Check class="size-4 text-zinc-400" />
                    </button>
                    <div
                      v-if="filteredMembers.length === 0"
                      class="px-3 py-4 text-sm text-zinc-500"
                    >
                      Anggota tidak ditemukan.
                    </div>
                  </div>
                </div>
                <InputError :message="form.errors.cooperative_member_id" />

                <div
                  v-if="selectedMember"
                  class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200/70 bg-zinc-50/90 px-3 py-2 text-sm dark:border-zinc-800/70 dark:bg-zinc-950/70"
                >
                  <div>
                    <div class="font-medium text-zinc-950 dark:text-zinc-50">
                      {{ selectedMember.name }}
                    </div>
                    <div class="text-xs text-zinc-500">
                      No. Anggota {{ selectedMember.member_no }}
                    </div>
                  </div>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="clearSelectedMember"
                  >
                    Ganti
                  </Button>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="cooperative_contribution_type_id"
                  >Jenis Simpanan</Label
                >
                <select
                  id="cooperative_contribution_type_id"
                  v-model="form.cooperative_contribution_type_id"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="">Pilih jenis simpanan</option>
                  <option
                    v-for="type in contributionTypes"
                    :key="type.id"
                    :value="String(type.id)"
                  >
                    {{ type.name }}
                  </option>
                </select>
                <InputError
                  :message="form.errors.cooperative_contribution_type_id"
                />
              </div>

              <div
                class="rounded-xl border border-emerald-200/80 bg-emerald-50/80 p-4 text-sm shadow-sm shadow-emerald-950/5 dark:border-emerald-500/20 dark:bg-emerald-500/10"
              >
                <div
                  class="flex items-center gap-2 font-medium text-zinc-950 dark:text-zinc-50"
                >
                  <WalletCards class="size-4" />
                  Aturan Nominal
                </div>
                <p class="mt-2 leading-6 text-zinc-600 dark:text-zinc-300">
                  {{ amountHelper }}
                </p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="amount">Nominal</Label>
                  <Input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    min="1"
                    :readonly="isFixedAmount"
                    placeholder="Masukkan nominal"
                    required
                  />
                  <InputError :message="form.errors.amount" />
                </div>

                <div class="space-y-2">
                  <Label for="payment_method">Metode Pembayaran</Label>
                  <select
                    id="payment_method"
                    v-model="form.payment_method"
                    class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:border-zinc-800 dark:bg-zinc-950"
                  >
                    <option value="CASH">Tunai</option>
                    <option value="TRANSFER">Transfer</option>
                    <option value="QRIS">QRIS</option>
                  </select>
                  <InputError :message="form.errors.payment_method" />
                </div>
              </div>

              <div class="space-y-2">
                <Label for="paid_at">Tanggal Pembayaran</Label>
                <Input
                  id="paid_at"
                  v-model="form.paid_at"
                  type="date"
                  required
                />
                <InputError :message="form.errors.paid_at" />
              </div>

              <div class="space-y-2">
                <Label for="notes">Keterangan</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  :rows="3"
                  placeholder="Contoh: setoran simpanan sukarela anggota"
                />
                <InputError :message="form.errors.notes" />
              </div>

              <div class="space-y-2">
                <Label for="proof">Bukti Pembayaran</Label>
                <Input
                  id="proof"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg"
                  @change="handleProofChange"
                />
                <div class="flex items-center gap-2 text-xs text-zinc-500">
                  <ImagePlus class="size-3.5" />
                  Upload gambar bukti transfer atau bukti setor.
                </div>
                <div v-if="proofFileName" class="text-xs text-zinc-500">
                  File dipilih: {{ proofFileName }}
                </div>
                <InputError :message="form.errors.proof" />
              </div>

              <Button
                class="w-full shadow-sm"
                type="submit"
                :disabled="form.processing"
              >
                {{ form.processing ? "Menyimpan…" : "Submit Pembayaran" }}
              </Button>
            </form>
          </CardContent>
        </Card>

        <div class="space-y-6">
          <div class="grid gap-4 sm:grid-cols-3">
            <div
              class="rounded-xl border border-zinc-200/70 bg-white/80 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <div
                class="text-xs font-medium uppercase tracking-wide text-zinc-500"
              >
                Anggota
              </div>
              <div
                class="mt-1 truncate text-sm font-semibold text-zinc-950 dark:text-white"
              >
                {{ selectedMember ? selectedMember.name : "Belum dipilih" }}
              </div>
              <div v-if="selectedMember" class="text-xs text-zinc-500">
                {{ selectedMember.member_no }}
              </div>
            </div>
            <div
              class="rounded-xl border border-zinc-200/70 bg-white/80 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <div
                class="text-xs font-medium uppercase tracking-wide text-zinc-500"
              >
                Jenis Simpanan
              </div>
              <div
                class="mt-1 truncate text-sm font-semibold text-zinc-950 dark:text-white"
              >
                {{ selectedContributionType?.name ?? "Belum dipilih" }}
              </div>
            </div>
            <div
              class="rounded-xl border border-zinc-200/70 bg-white/80 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <div
                class="text-xs font-medium uppercase tracking-wide text-zinc-500"
              >
                Nominal
              </div>
              <div
                class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white"
              >
                {{ form.amount ? formatCurrency(form.amount) : "-" }}
              </div>
            </div>
          </div>

          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="Riwayat Pembayaran"
              :description="`${payments.total ?? 0} transaksi tercatat`"
              :icon="Layers"
              tone="emerald"
            />
            <CardContent class="px-0 pb-0">
              <BulkActionBar
                :selected="selectedPayments"
                :actions="canApprovePayments ? [{ label: 'Approve Semua', action: 'approve', variant: 'default' as const }] : []"
                @action="handleBulkAction"
                @clear="selectedPayments = []"
              />
              <DataTable
                :columns="columns"
                :data="payments"
                :searchable="false"
                :empty-icon="ReceiptText"
                empty-message="Belum ada pembayaran yang tercatat."
                :selectable="canApprovePayments"
                :selected="selectedPayments"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @selection-change="selectedPayments = $event"
                @sort="handleSort"
              >
                <template #paid_at="{ row }">
                  <span class="tabular-nums">{{
                    formatDate(row.paid_at)
                  }}</span>
                </template>

                <template #member="{ row }">
                  <div class="font-semibold text-zinc-950 dark:text-white">
                    {{ row.member?.name || "-" }}
                  </div>
                  <div class="text-xs text-zinc-500">
                    {{ row.member?.member_no || "-" }}
                  </div>
                </template>

                <template #type="{ row }">
                  <Badge
                    variant="outline"
                    class="bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
                  >
                    {{
                      row.contribution_type?.name ||
                      row.invoice?.contribution_type?.name ||
                      "-"
                    }}
                  </Badge>
                </template>

                <template #method="{ row }">
                  <Badge
                    variant="outline"
                    class="bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                  >
                    {{ row.payment_method }}
                  </Badge>
                </template>

                <template #status="{ row }">
                  <Badge
                    variant="outline"
                    :class="
                      row.status === 'APPROVED'
                        ? 'bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                        : row.status === 'PENDING'
                          ? 'bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
                          : 'bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                    "
                  >
                    {{ row.status }}
                  </Badge>
                </template>

                <template #amount="{ value }">
                  <span
                    class="font-semibold tabular-nums text-emerald-700 dark:text-emerald-300"
                  >
                    {{ formatCurrency(value) }}
                  </span>
                </template>

                <template #notes="{ row }">
                  <span
                    class="block max-w-[220px] truncate text-zinc-600 dark:text-zinc-400"
                    :title="row.notes || ''"
                  >
                    {{ row.notes || "-" }}
                  </span>
                </template>

                <template v-if="canApprovePayments" #actions="{ row }">
                  <Button
                    v-if="row.status === 'PENDING'"
                    size="sm"
                    class="h-8"
                    :disabled="approvingPaymentId === row.id"
                    @click="approvePayment(row)"
                  >
                    <CheckCircle2 class="size-4" />
                    {{ approvingPaymentId === row.id ? "Memproses" : "Approve" }}
                  </Button>
                  <span v-else class="text-xs text-zinc-400">-</span>
                </template>
              </DataTable>
            </CardContent>
          </Card>
        </div>
      </div>
    </PageContainer>

    <ConfirmDialog
      v-if="pendingBulkAction"
      v-model:open="showBulkConfirm"
      title="Konfirmasi Approve Massal"
      confirm-label="Setujui"
      :message="`${pendingBulkAction.selected.length} pembayaran akan disetujui. Hanya pembayaran berstatus PENDING yang akan diproses.`"
      @confirm="confirmBulkAction"
    />
  </AppLayout>
</template>
