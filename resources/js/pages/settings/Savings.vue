<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import InputError from "@/components/InputError.vue";
import Heading from "@/components/Heading.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import { edit, update } from "@/routes/settings/savings";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  settings: {
    wajib: {
      id: number;
      code: string;
      name: string;
      default_amount: number | string;
      frequency: string;
    };
    pokok: {
      id: number;
      code: string;
      name: string;
      default_amount: number | string;
      frequency: string;
    };
  };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: "Savings settings",
    href: edit(),
  },
];

const form = useForm({
  wajib_default_amount: Number(props.settings.wajib.default_amount ?? 0),
  pokok_default_amount: Number(props.settings.pokok.default_amount ?? 0),
});

const submit = () => {
  form.put(update().url, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Savings settings" />

    <h1 class="sr-only">Savings Settings</h1>

    <SettingsLayout contentWrapperClass="max-w-none" contentClass="max-w-none space-y-6">
      <div class="space-y-6">
        <Heading
          variant="small"
          title="Savings settings"
          description="Atur nominal simpanan wajib bulanan dan simpanan pokok pendaftaran anggota."
        />

        <div class="grid gap-4">
          <div
            class="rounded-xl border border-emerald-200/80 bg-emerald-50/80 p-5 shadow-sm shadow-emerald-950/5 dark:border-emerald-500/20 dark:bg-emerald-500/10"
          >
            <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">
              Dampak ke Tagihan Iuran
            </div>
            <div class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
              <p>
                Setelah disimpan, nominal baru langsung menjadi acuan pada halaman
                `Tagihan Iuran`.
              </p>
              <p>
                Tagihan yang sudah terbit tidak berubah otomatis. Nilai baru dipakai
                untuk generate periode berikutnya dan untuk simpanan pokok anggota baru.
              </p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              <div class="rounded-lg border border-white/60 bg-white/80 p-3 dark:border-zinc-700/60 dark:bg-zinc-950/40">
                <div class="text-xs uppercase tracking-wide text-zinc-500">
                  Simpanan Wajib
                </div>
                <div class="mt-1 text-lg font-semibold text-zinc-950 dark:text-zinc-50">
                  {{ formatCurrency(form.wajib_default_amount) }}
                </div>
              </div>
              <div class="rounded-lg border border-white/60 bg-white/80 p-3 dark:border-zinc-700/60 dark:bg-zinc-950/40">
                <div class="text-xs uppercase tracking-wide text-zinc-500">
                  Simpanan Pokok
                </div>
                <div class="mt-1 text-lg font-semibold text-zinc-950 dark:text-zinc-50">
                  {{ formatCurrency(form.pokok_default_amount) }}
                </div>
              </div>
            </div>
          </div>

          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <CardContent
              class="space-y-6 bg-gradient-to-br from-white to-zinc-50 py-6 dark:from-zinc-900 dark:to-zinc-950"
            >
              <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-6 md:grid-cols-2">
                  <div class="space-y-3 rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-5 dark:border-zinc-800/70 dark:bg-zinc-950">
                    <div>
                      <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">
                        Simpanan Wajib
                      </div>
                      <p class="mt-1 text-sm text-zinc-500">
                        Dipakai untuk generate tagihan bulanan anggota aktif.
                      </p>
                    </div>
                    <div class="space-y-2">
                      <Label for="wajib_default_amount">Nominal per bulan</Label>
                      <Input
                        id="wajib_default_amount"
                        v-model="form.wajib_default_amount"
                        type="number"
                        min="0"
                        step="1000"
                      />
                      <InputError :message="form.errors.wajib_default_amount" />
                    </div>
                    <div class="text-xs text-zinc-500">
                      Nominal saat ini:
                      {{ formatCurrency(form.wajib_default_amount) }}
                    </div>
                  </div>

                  <div class="space-y-3 rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-5 dark:border-zinc-800/70 dark:bg-zinc-950">
                    <div>
                      <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">
                        Simpanan Pokok
                      </div>
                      <p class="mt-1 text-sm text-zinc-500">
                        Dipakai untuk tagihan satu kali saat anggota baru dibuat atau diaktifkan.
                      </p>
                    </div>
                    <div class="space-y-2">
                      <Label for="pokok_default_amount">Nominal pendaftaran</Label>
                      <Input
                        id="pokok_default_amount"
                        v-model="form.pokok_default_amount"
                        type="number"
                        min="0"
                        step="1000"
                      />
                      <InputError :message="form.errors.pokok_default_amount" />
                    </div>
                    <div class="text-xs text-zinc-500">
                      Nominal saat ini:
                      {{ formatCurrency(form.pokok_default_amount) }}
                    </div>
                  </div>
                </div>

                <div class="flex justify-end">
                  <Button type="submit" :disabled="form.processing">
                    Simpan Pengaturan
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
