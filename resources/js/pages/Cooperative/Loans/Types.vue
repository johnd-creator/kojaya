<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Plus, Settings, Sparkles } from "lucide-vue-next";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, store, update, destroy } from "@/routes/cooperative/loan-types";

defineProps<{ loanTypes: any[] }>();

const createForm = useForm({
  code: "",
  name: "",
  description: "",
  interest_rate: 1.5,
  admin_fee: 25000,
  late_fee_per_day: 2500,
  min_amount: 500000,
  max_amount: 10000000,
  min_term_months: 3,
  max_term_months: 12,
  is_active: true,
});

const editId = ref<number | null>(null);
const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteId = ref<number | null>(null);
const deleteDialogOpen = computed({
  get: () => deleteId.value !== null,
  set: (open: boolean) => {
    if (!open) deleteId.value = null;
  },
});
const editForm = useForm({
  code: "",
  name: "",
  description: "",
  interest_rate: 0,
  admin_fee: 0,
  late_fee_per_day: 0,
  min_amount: 0,
  max_amount: 0,
  min_term_months: 1,
  max_term_months: 1,
  is_active: true,
});

const openCreateDialog = () => {
  createForm.reset();
  createForm.clearErrors();
  createDialogOpen.value = true;
};

const startEdit = (loanType: any) => {
  editId.value = loanType.id;
  editForm.code = loanType.code;
  editForm.name = loanType.name;
  editForm.description = loanType.description ?? "";
  editForm.interest_rate = Number(loanType.interest_rate);
  editForm.admin_fee = Number(loanType.admin_fee);
  editForm.late_fee_per_day = Number(loanType.late_fee_per_day);
  editForm.min_amount = Number(loanType.min_amount);
  editForm.max_amount = Number(loanType.max_amount);
  editForm.min_term_months = Number(loanType.min_term_months);
  editForm.max_term_months = Number(loanType.max_term_months);
  editForm.is_active = Boolean(loanType.is_active);
  editForm.clearErrors();
  editDialogOpen.value = true;
};

const submitCreate = () =>
  createForm.post(store().url, {
    preserveScroll: true,
    onSuccess: () => {
      createDialogOpen.value = false;
      createForm.reset();
    },
  });
const submitEdit = () => {
  if (!editId.value) return;
  editForm.put(update(editId.value).url, {
    onSuccess: () => {
      editDialogOpen.value = false;
      editId.value = null;
    },
  });
};
const submitDelete = () => {
  if (!deleteId.value) return;
  useForm({}).delete(destroy(deleteId.value).url, {
    onSuccess: () => {
      deleteId.value = null;
    },
  });
};

const columns = [
  { header: "Tipe Pinjaman", key: "name", slot: "loan_type" },
  {
    header: "Bunga",
    key: "interest_rate",
    slot: "interest",
    align: "right" as const,
  },
  {
    header: "Biaya Admin",
    key: "admin_fee",
    slot: "admin_fee",
    align: "right" as const,
  },
  {
    header: "Denda / Hari",
    key: "late_fee_per_day",
    slot: "late_fee",
    align: "right" as const,
  },
  { header: "Plafon", key: "min_amount", slot: "amount" },
  { header: "Tenor", key: "min_term_months", slot: "term" },
  { header: "Status", key: "is_active", slot: "status" },
  { header: "Aksi", key: "id", slot: "actions", align: "right" as const },
];
</script>

<template>
  <Head title="Tipe Pinjaman" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Tipe Pinjaman', href: index().url },
    ]"
  >
    <PageContainer class="max-w-6xl">
      <section
        class="relative overflow-hidden rounded-2xl border border-sky-200/60 bg-gradient-to-br from-white via-sky-50/60 to-emerald-50/40 p-6 shadow-sm shadow-sky-950/5 sm:p-7 dark:border-sky-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"
          aria-hidden="true"
        />
        <div class="relative space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-900/40 dark:text-sky-200 dark:ring-sky-800/60"
          >
            <Sparkles class="size-3.5" />
            Konfigurasi
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Tipe Pinjaman
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Parameter bunga, biaya, dan tenor untuk setiap produk pinjaman.
          </p>
        </div>
      </section>

      <div class="flex justify-end">
        <Dialog v-model:open="createDialogOpen">
          <Button type="button" @click="openCreateDialog">
            <Plus class="mr-2 size-4" /> Tambah Tipe Pinjaman
          </Button>
          <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
              <DialogTitle>Tambah Tipe Pinjaman</DialogTitle>
              <DialogDescription>
                Tentukan bunga, biaya, plafon, dan tenor untuk produk pinjaman
                baru.
              </DialogDescription>
            </DialogHeader>
            <form
              class="grid gap-4 sm:grid-cols-2"
              @submit.prevent="submitCreate"
            >
              <Input v-model="createForm.code" placeholder="Kode" required />
              <Input
                v-model="createForm.name"
                placeholder="Nama tipe pinjaman"
                required
              />
              <Input
                v-model="createForm.interest_rate"
                type="number"
                step="0.01"
                min="0"
                placeholder="Bunga %"
                required
              />
              <Input
                v-model="createForm.admin_fee"
                type="number"
                min="0"
                step="1000"
                placeholder="Biaya admin"
                required
              />
              <Input
                v-model="createForm.late_fee_per_day"
                type="number"
                min="0"
                step="1000"
                placeholder="Denda / hari"
                required
              />
              <Input
                v-model="createForm.min_amount"
                type="number"
                min="0"
                step="1000"
                placeholder="Minimum pinjaman"
                required
              />
              <Input
                v-model="createForm.max_amount"
                type="number"
                min="0"
                step="1000"
                placeholder="Maksimum pinjaman"
                required
              />
              <Input
                v-model="createForm.min_term_months"
                type="number"
                min="1"
                placeholder="Tenor minimum"
                required
              />
              <Input
                v-model="createForm.max_term_months"
                type="number"
                min="1"
                placeholder="Tenor maksimum"
                required
              />
              <textarea
                v-model="createForm.description"
                class="min-h-20 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950 sm:col-span-2"
                placeholder="Deskripsi"
              />
              <label class="flex items-center gap-2 text-sm">
                <input
                  v-model="createForm.is_active"
                  type="checkbox"
                  class="rounded border"
                />
                Aktif
              </label>
              <DialogFooter class="sm:col-span-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="createDialogOpen = false"
                >
                  Batal
                </Button>
                <Button type="submit" :disabled="createForm.processing">
                  Simpan Tipe
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card
        data-testid="loan-types-list-card"
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Daftar Tipe Pinjaman"
          :description="`${loanTypes.length} tipe pinjaman tersedia`"
          :icon="Settings"
          tone="sky"
        />
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="columns"
            :data="loanTypes"
            table-class="min-w-[900px]"
            compact
            :searchable="false"
            :empty-icon="Settings"
            empty-message="Belum ada tipe pinjaman yang tersedia."
          >
            <template #loan_type="{ row }">
              <div class="font-semibold text-zinc-950 dark:text-white">
                {{ row.name }}
              </div>
              <div class="mt-1 flex min-w-0 items-center gap-2">
                <Badge
                  variant="outline"
                  class="shrink-0 bg-zinc-100 px-1.5 py-0 text-[10px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                >
                  {{ row.code }}
                </Badge>
                <span
                  class="block max-w-[18rem] truncate text-xs text-zinc-500"
                  :title="row.description || ''"
                >
                  {{ row.description || "Tanpa keterangan" }}
                </span>
              </div>
            </template>
            <template #interest="{ value }">
              <span class="tabular-nums">{{ value }}%</span>
            </template>
            <template #admin_fee="{ value }">
              <span class="tabular-nums">{{ formatCurrency(value) }}</span>
            </template>
            <template #late_fee="{ value }">
              <span class="tabular-nums">{{ formatCurrency(value) }}</span>
            </template>
            <template #amount="{ row }">
              <span class="whitespace-nowrap tabular-nums">
                {{ formatCurrency(row.min_amount) }} –
                {{ formatCurrency(row.max_amount) }}
              </span>
            </template>
            <template #term="{ row }">
              <span class="whitespace-nowrap tabular-nums">
                {{ row.min_term_months }}–{{ row.max_term_months }} bln
              </span>
            </template>
            <template #status="{ value }">
              <Badge
                variant="outline"
                :class="
                  value
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
                "
              >
                {{ value ? "AKTIF" : "NONAKTIF" }}
              </Badge>
            </template>
            <template #actions="{ row }">
              <div class="flex justify-end gap-2">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="startEdit(row)"
                >
                  Edit
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  @click="deleteId = row.id"
                >
                  Hapus
                </Button>
              </div>
            </template>
          </DataTable>
        </CardContent>
      </Card>

      <Dialog v-model:open="editDialogOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Edit Tipe Pinjaman</DialogTitle>
            <DialogDescription>
              Perbarui parameter produk pinjaman tanpa mengubah riwayat
              transaksi.
            </DialogDescription>
          </DialogHeader>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitEdit">
            <Input v-model="editForm.code" required />
            <Input v-model="editForm.name" required />
            <Input
              v-model="editForm.interest_rate"
              type="number"
              step="0.01"
              min="0"
              required
            />
            <Input
              v-model="editForm.admin_fee"
              type="number"
              min="0"
              step="1000"
              required
            />
            <Input
              v-model="editForm.late_fee_per_day"
              type="number"
              min="0"
              step="1000"
              required
            />
            <Input
              v-model="editForm.min_amount"
              type="number"
              min="0"
              step="1000"
              required
            />
            <Input
              v-model="editForm.max_amount"
              type="number"
              min="0"
              step="1000"
              required
            />
            <Input
              v-model="editForm.min_term_months"
              type="number"
              min="1"
              required
            />
            <Input
              v-model="editForm.max_term_months"
              type="number"
              min="1"
              required
            />
            <textarea
              v-model="editForm.description"
              class="min-h-20 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950 sm:col-span-2"
            />
            <label class="flex items-center gap-2 text-sm">
              <input
                v-model="editForm.is_active"
                type="checkbox"
                class="rounded border"
              />
              Aktif
            </label>
            <DialogFooter class="sm:col-span-2">
              <Button
                type="button"
                variant="outline"
                @click="editDialogOpen = false"
              >
                Batal
              </Button>
              <Button type="submit" :disabled="editForm.processing">
                Simpan Perubahan
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        v-model:open="deleteDialogOpen"
        title="Hapus Tipe Pinjaman"
        message="Tipe pinjaman yang tidak dipakai lagi bisa dihapus. Data yang sudah direferensikan sebaiknya dinonaktifkan."
        confirm-label="Hapus"
        variant="danger"
        @confirm="submitDelete"
      />
    </PageContainer>
  </AppLayout>
</template>
