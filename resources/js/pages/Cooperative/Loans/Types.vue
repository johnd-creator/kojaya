<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, store, update, destroy } from "@/routes/cooperative/loan-types";

const props = defineProps<{ loanTypes: any[] }>();

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
const deleteId = ref<number | null>(null);
const deleteDialogOpen = computed({
  get: () => deleteId.value !== null,
  set: (open: boolean) => {
    if (!open) {
      deleteId.value = null;
    }
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
};

const submitCreate = () => createForm.post(store().url);
const submitEdit = () => {
  if (!editId.value) return;
  editForm.put(update(editId.value).url, {
    onSuccess: () => {
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
</script>

<template>
  <Head title="Tipe Pinjaman" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Tipe Pinjaman', href: index().url },
    ]"
  >
    <PageContainer variant="detail" class="max-w-none">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Tipe Pinjaman</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Parameter bunga, biaya, dan tenor untuk setiap produk pinjaman.
        </p>
      </div>

      <form class="grid gap-4 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900 md:grid-cols-3" @submit.prevent="submitCreate">
        <Input v-model="createForm.code" placeholder="Kode" required />
        <Input v-model="createForm.name" placeholder="Nama tipe pinjaman" required />
        <Input v-model="createForm.interest_rate" type="number" step="0.01" min="0" placeholder="Bunga %" required />
        <Input v-model="createForm.admin_fee" type="number" min="0" step="1000" placeholder="Biaya admin" required />
        <Input v-model="createForm.late_fee_per_day" type="number" min="0" step="1000" placeholder="Denda / hari" required />
        <Input v-model="createForm.min_amount" type="number" min="0" step="1000" placeholder="Minimum pinjaman" required />
        <Input v-model="createForm.max_amount" type="number" min="0" step="1000" placeholder="Maksimum pinjaman" required />
        <Input v-model="createForm.min_term_months" type="number" min="1" placeholder="Tenor minimum" required />
        <Input v-model="createForm.max_term_months" type="number" min="1" placeholder="Tenor maksimum" required />
        <textarea v-model="createForm.description" class="min-h-20 rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 md:col-span-2" placeholder="Deskripsi" />
        <label class="flex items-center gap-2 text-sm">
          <input v-model="createForm.is_active" type="checkbox" class="rounded border" />
          Aktif
        </label>
        <div class="md:col-span-3 flex justify-end">
          <Button type="submit" :disabled="createForm.processing">Simpan Tipe</Button>
        </div>
      </form>

      <div class="space-y-4">
        <div
          v-for="loanType in loanTypes"
          :key="loanType.id"
          class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900"
        >
          <div v-if="editId !== loanType.id" class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
              <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold">{{ loanType.name }}</h2>
                <span class="rounded-full border px-2 py-0.5 text-xs">{{ loanType.code }}</span>
                <span class="rounded-full border px-2 py-0.5 text-xs">{{ loanType.is_active ? "ACTIVE" : "INACTIVE" }}</span>
              </div>
              <p class="mt-2 text-sm text-zinc-500">{{ loanType.description || "-" }}</p>
              <div class="mt-4 grid gap-2 text-sm md:grid-cols-3">
                <div>Bunga: {{ loanType.interest_rate }}%</div>
                <div>Admin: {{ formatCurrency(loanType.admin_fee) }}</div>
                <div>Denda: {{ formatCurrency(loanType.late_fee_per_day) }}/hari</div>
                <div>Min nominal: {{ formatCurrency(loanType.min_amount) }}</div>
                <div>Max nominal: {{ formatCurrency(loanType.max_amount) }}</div>
                <div>Tenor: {{ loanType.min_term_months }}-{{ loanType.max_term_months }} bulan</div>
              </div>
            </div>
            <div class="flex gap-2">
              <Button type="button" variant="outline" @click="startEdit(loanType)">Edit</Button>
              <Button type="button" variant="destructive" @click="deleteId = loanType.id">Hapus</Button>
            </div>
          </div>

          <form v-else class="grid gap-4 md:grid-cols-3" @submit.prevent="submitEdit">
            <Input v-model="editForm.code" required />
            <Input v-model="editForm.name" required />
            <Input v-model="editForm.interest_rate" type="number" step="0.01" min="0" required />
            <Input v-model="editForm.admin_fee" type="number" min="0" step="1000" required />
            <Input v-model="editForm.late_fee_per_day" type="number" min="0" step="1000" required />
            <Input v-model="editForm.min_amount" type="number" min="0" step="1000" required />
            <Input v-model="editForm.max_amount" type="number" min="0" step="1000" required />
            <Input v-model="editForm.min_term_months" type="number" min="1" required />
            <Input v-model="editForm.max_term_months" type="number" min="1" required />
            <textarea v-model="editForm.description" class="min-h-20 rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 md:col-span-2" />
            <label class="flex items-center gap-2 text-sm">
              <input v-model="editForm.is_active" type="checkbox" class="rounded border" />
              Aktif
            </label>
            <div class="md:col-span-3 flex justify-end gap-2">
              <Button type="button" variant="outline" @click="editId = null">Batal</Button>
              <Button type="submit" :disabled="editForm.processing">Simpan Perubahan</Button>
            </div>
          </form>
        </div>
      </div>

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
