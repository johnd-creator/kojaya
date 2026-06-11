<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Settings, Sparkles } from "lucide-vue-next";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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
    <PageContainer class="max-w-none">
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

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader title="Tipe Baru" :icon="Settings" tone="sky" />
        <CardContent class="p-5">
          <form
            class="grid gap-4 md:grid-cols-3"
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
              class="min-h-20 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950 md:col-span-2"
              placeholder="Deskripsi"
            />
            <label class="flex items-center gap-2 text-sm"
              ><input
                v-model="createForm.is_active"
                type="checkbox"
                class="rounded border"
              />
              Aktif</label
            >
            <div class="md:col-span-3 flex justify-end">
              <Button
                type="submit"
                :disabled="createForm.processing"
                class="shadow-sm"
                >Simpan Tipe</Button
              >
            </div>
          </form>
        </CardContent>
      </Card>

      <div class="space-y-4">
        <Card
          v-for="loanType in loanTypes"
          :key="loanType.id"
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div
            v-if="editId !== loanType.id"
            class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between"
          >
            <div class="min-w-0 flex-1 space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                  {{ loanType.name }}
                </h2>
                <Badge
                  variant="outline"
                  class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                  >{{ loanType.code }}</Badge
                >
                <Badge
                  variant="outline"
                  :class="
                    loanType.is_active
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                      : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
                  "
                  >{{ loanType.is_active ? "AKTIF" : "NONAKTIF" }}</Badge
                >
              </div>
              <p class="text-sm text-zinc-500">
                {{ loanType.description || "-" }}
              </p>
              <div class="grid gap-2 text-sm md:grid-cols-3">
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Bunga</span>
                  <span class="ml-1 font-semibold"
                    >{{ loanType.interest_rate }}%</span
                  >
                </div>
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Admin</span>
                  <span class="ml-1 font-semibold">{{
                    formatCurrency(loanType.admin_fee)
                  }}</span>
                </div>
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Denda</span>
                  <span class="ml-1 font-semibold"
                    >{{ formatCurrency(loanType.late_fee_per_day) }}/hari</span
                  >
                </div>
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Min</span>
                  <span class="ml-1 font-semibold">{{
                    formatCurrency(loanType.min_amount)
                  }}</span>
                </div>
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Max</span>
                  <span class="ml-1 font-semibold">{{
                    formatCurrency(loanType.max_amount)
                  }}</span>
                </div>
                <div
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <span class="text-zinc-500">Tenor</span>
                  <span class="ml-1 font-semibold"
                    >{{ loanType.min_term_months }}-{{
                      loanType.max_term_months
                    }}
                    bln</span
                  >
                </div>
              </div>
            </div>
            <div class="flex gap-2 shrink-0">
              <Button
                type="button"
                variant="outline"
                @click="startEdit(loanType)"
                >Edit</Button
              >
              <Button
                type="button"
                variant="destructive"
                @click="deleteId = loanType.id"
                >Hapus</Button
              >
            </div>
          </div>

          <form
            v-else
            class="grid gap-4 p-5 md:grid-cols-3"
            @submit.prevent="submitEdit"
          >
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
              class="min-h-20 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950 md:col-span-2"
            />
            <label class="flex items-center gap-2 text-sm"
              ><input
                v-model="editForm.is_active"
                type="checkbox"
                class="rounded border"
              />
              Aktif</label
            >
            <div class="md:col-span-3 flex justify-end gap-2">
              <Button type="button" variant="outline" @click="editId = null"
                >Batal</Button
              >
              <Button type="submit" :disabled="editForm.processing"
                >Simpan Perubahan</Button
              >
            </div>
          </form>
        </Card>
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
