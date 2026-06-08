<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
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
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index } from "@/routes/cooperative/ledger";

const props = defineProps<{
  entries: any;
  filters: any;
  summary: {
    total_balance: number;
    by_category: Record<string, number>;
    uncategorized: number;
  };
  contributionTypes: any[];
  entryTypes: string[];
  canManageLedger: boolean;
}>();
const memberSearch = ref(props.filters.member_search ?? "");
const entryType = ref(props.filters.entry_type ?? "");
const ledgerScope = ref(props.filters.ledger_scope ?? "SAVINGS");
const contributionTypeId = ref(props.filters.contribution_type_id ?? "");
const startDate = ref(props.filters.start_date ?? "");
const endDate = ref(props.filters.end_date ?? "");
const revisionDialogOpen = ref(false);
const selectedEntry = ref<any>(null);
const revisionForm = useForm({
  amount: "",
  payment_method: "CASH",
  paid_at: "",
  notes: "",
  reason: "",
});
const cancelForm = useForm({
  reason: "",
});
const applyFilters = () =>
  router.get(
    index().url,
    {
      member_search: memberSearch.value,
      entry_type: entryType.value,
      ledger_scope: ledgerScope.value,
      contribution_type_id: contributionTypeId.value,
      start_date: startDate.value,
      end_date: endDate.value,
    },
    { preserveState: true, replace: true },
  );

const canCorrectEntry = (entry: any) =>
  props.canManageLedger &&
  entry.entry_type === "SAVING_PAYMENT" &&
  Boolean(entry.cooperative_payment_id);

const openRevisionDialog = (entry: any) => {
  selectedEntry.value = entry;
  revisionForm.clearErrors();
  revisionForm.amount = String(Number(entry.credit || entry.debit || 0));
  revisionForm.payment_method = entry.payment?.payment_method ?? "CASH";
  revisionForm.paid_at = String(entry.posted_at ?? "").slice(0, 10);
  revisionForm.notes = entry.description ?? "";
  revisionForm.reason = "";
  revisionDialogOpen.value = true;
};

const submitRevision = () => {
  if (!selectedEntry.value) {
    return;
  }

  revisionForm.post(
    `/cooperative/ledger/${selectedEntry.value.id}/revise-payment`,
    {
      preserveScroll: true,
      onSuccess: () => {
        revisionDialogOpen.value = false;
        selectedEntry.value = null;
        revisionForm.reset();
      },
    },
  );
};

const cancelPayment = (entry: any) => {
  const reason = window.prompt("Alasan cancel transaksi ini?");

  if (!reason?.trim()) {
    return;
  }

  cancelForm.reason = reason.trim();
  cancelForm.post(`/cooperative/ledger/${entry.id}/cancel-payment`, {
    preserveScroll: true,
    onFinish: () => cancelForm.reset(),
  });
};
</script>

<template>
  <Head title="Ledger Simpanan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Iuran & Simpanan', href: '#' },
      { title: 'Ledger Simpanan', href: index().url },
    ]"
  >
    <div class="flex w-full flex-col gap-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Ledger Simpanan</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Monitoring mutasi simpanan anggota berdasarkan kategori.
        </p>
      </div>
      <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
        >
          <div class="text-sm text-zinc-500">Total Simpanan</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.total_balance) }}
          </div>
        </div>
        <div
          v-for="type in contributionTypes"
          :key="type.id"
          class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
        >
          <div class="text-sm text-zinc-500">{{ type.name }}</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.by_category?.[type.category] ?? 0) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
        >
          <div class="text-sm text-zinc-500">Belum Dikategorikan</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.uncategorized) }}
          </div>
        </div>
      </div>
      <div
        class="grid gap-3 rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900 md:grid-cols-3 xl:grid-cols-6"
      >
        <Input
          v-model="memberSearch"
          placeholder="Cari nama / no anggota"
          @keyup.enter="applyFilters"
        />
        <select
          v-model="ledgerScope"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="SAVINGS">Simpanan</option>
          <option value="LOAN">Pinjaman</option>
          <option value="POS">POS</option>
          <option value="">Semua scope</option>
        </select>
        <select
          v-model="contributionTypeId"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua jenis simpanan</option>
          <option
            v-for="type in contributionTypes"
            :key="type.id"
            :value="type.id"
          >
            {{ type.name }}
          </option>
        </select>
        <select
          v-model="entryType"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua tipe</option>
          <option v-for="item in entryTypes" :key="item" :value="item">
            {{ item }}
          </option>
        </select>
        <Input v-model="startDate" type="date" />
        <Input v-model="endDate" type="date" />
        <Button variant="outline" @click="applyFilters">Filter</Button>
      </div>
      <div
        class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
      >
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Tanggal</th>
              <th>No Anggota</th>
              <th>Nama Anggota</th>
              <th>Jenis Simpanan</th>
              <th>Tipe Mutasi</th>
              <th>Scope</th>
              <th class="text-right">Debit</th>
              <th class="text-right">Kredit</th>
              <th class="px-4 py-3">Keterangan</th>
              <th v-if="canManageLedger" class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="entry in entries.data" :key="entry.id">
              <td class="px-4 py-3">{{ formatDate(entry.posted_at) }}</td>
              <td>
                {{ entry.member?.member_no || entry.member?.no_anggota || "-" }}
              </td>
              <td class="font-medium text-zinc-950 dark:text-zinc-50">
                {{ entry.member?.name || entry.member?.nama_anggota || "-" }}
              </td>
              <td>
                {{
                  entry.contribution_type?.name ||
                  entry.category_snapshot ||
                  "-"
                }}
              </td>
              <td>{{ entry.entry_type }}</td>
              <td>{{ entry.ledger_scope || "-" }}</td>
              <td class="text-right">{{ formatCurrency(entry.debit) }}</td>
              <td class="text-right">{{ formatCurrency(entry.credit) }}</td>
              <td class="px-4 py-3">{{ entry.description || "-" }}</td>
              <td v-if="canManageLedger" class="px-4 py-3 text-right">
                <div
                  v-if="canCorrectEntry(entry)"
                  class="flex justify-end gap-2"
                >
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="openRevisionDialog(entry)"
                  >
                    Revisi
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    :disabled="cancelForm.processing"
                    @click="cancelPayment(entry)"
                  >
                    Cancel
                  </Button>
                </div>
                <span v-else class="text-xs text-zinc-400">-</span>
              </td>
            </tr>
            <tr v-if="entries.data.length === 0">
              <td
                :colspan="canManageLedger ? 10 : 9"
                class="px-4 py-10 text-center text-zinc-500"
              >
                Belum ada ledger.
              </td>
            </tr>
          </tbody>
        </table>
        <div
          v-if="entries.links?.length > 3"
          class="flex flex-col gap-3 border-t px-4 py-3 text-sm text-zinc-500 md:flex-row md:items-center md:justify-between"
        >
          <div>
            Menampilkan {{ entries.from }}-{{ entries.to }} dari
            {{ entries.total }} mutasi
          </div>
          <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in entries.links" :key="index">
              <Button
                v-if="link.url"
                as-child
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
              >
                <Link :href="link.url" preserve-scroll preserve-state>
                  <span v-html="link.label" />
                </Link>
              </Button>
              <span
                v-else
                class="rounded-md border px-3 py-1.5 text-zinc-400"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:open="revisionDialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Revisi Transaksi Ledger</DialogTitle>
          <DialogDescription>
            Revisi ini memperbarui payment, ledger, invoice, dan receipt
            terkait.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-4" @submit.prevent="submitRevision">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="revision_amount">Nominal</Label>
              <Input
                id="revision_amount"
                v-model="revisionForm.amount"
                type="number"
                min="1"
                required
              />
              <InputError :message="revisionForm.errors.amount" />
            </div>
            <div class="space-y-2">
              <Label for="revision_paid_at">Tanggal</Label>
              <Input
                id="revision_paid_at"
                v-model="revisionForm.paid_at"
                type="date"
                required
              />
              <InputError :message="revisionForm.errors.paid_at" />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="revision_payment_method">Metode Pembayaran</Label>
            <select
              id="revision_payment_method"
              v-model="revisionForm.payment_method"
              class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
              required
            >
              <option value="CASH">Tunai</option>
              <option value="TRANSFER">Transfer</option>
              <option value="QRIS">QRIS</option>
            </select>
            <InputError :message="revisionForm.errors.payment_method" />
          </div>

          <div class="space-y-2">
            <Label for="revision_notes">Keterangan Transaksi</Label>
            <Textarea
              id="revision_notes"
              v-model="revisionForm.notes"
              :rows="3"
            />
            <InputError :message="revisionForm.errors.notes" />
          </div>

          <div class="space-y-2">
            <Label for="revision_reason">Alasan Revisi</Label>
            <Textarea
              id="revision_reason"
              v-model="revisionForm.reason"
              :rows="3"
              required
            />
            <InputError :message="revisionForm.errors.reason" />
            <InputError :message="revisionForm.errors.ledger_entry" />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="revisionDialogOpen = false"
            >
              Batal
            </Button>
            <Button type="submit" :disabled="revisionForm.processing">
              Simpan Revisi
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
