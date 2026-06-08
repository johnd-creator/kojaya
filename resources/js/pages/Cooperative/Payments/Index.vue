<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Check, ImagePlus, Search, WalletCards } from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index, store } from "@/routes/cooperative/payments";

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

  if (!keyword) {
    return [];
  }

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
</script>

<template>
  <Head title="Pembayaran Koperasi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pembayaran', href: index().url },
    ]"
  >
    <div class="grid w-full gap-6 p-6 xl:grid-cols-[420px_1fr]">
      <form
        class="rounded-xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        @submit.prevent="submit"
      >
        <div class="space-y-1">
          <h1 class="text-xl font-semibold">Catat Pembayaran Simpanan</h1>
          <p class="text-sm text-zinc-500">
            Cari anggota dengan nama, pilih jenis simpanan, lalu simpan setoran.
          </p>
        </div>

        <div class="mt-5 space-y-5">
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
                    <div class="font-medium text-zinc-950 dark:text-zinc-50">
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
            <Label for="cooperative_contribution_type_id">Jenis Simpanan</Label>
            <select
              id="cooperative_contribution_type_id"
              v-model="form.cooperative_contribution_type_id"
              required
              class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
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
                placeholder="Masukkan nominal setoran"
                required
              />
              <InputError :message="form.errors.amount" />
            </div>

            <div class="space-y-2">
              <Label for="payment_method">Metode Pembayaran</Label>
              <select
                id="payment_method"
                v-model="form.payment_method"
                class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
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
            <Input id="paid_at" v-model="form.paid_at" type="date" required />
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
            Submit Pembayaran
          </Button>
        </div>
      </form>

      <div class="space-y-4">
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div class="text-sm font-medium text-zinc-950 dark:text-zinc-50">
            Ringkasan Input
          </div>
          <div class="mt-3 grid gap-3 md:grid-cols-3">
            <div
              class="rounded-lg border border-zinc-200/70 bg-zinc-50/90 p-3 dark:border-zinc-800/70 dark:bg-zinc-950"
            >
              <div class="text-xs uppercase tracking-wide text-zinc-500">
                Anggota
              </div>
              <div
                class="mt-1 text-sm font-medium text-zinc-950 dark:text-zinc-50"
              >
                {{
                  selectedMember
                    ? `${selectedMember.name} (${selectedMember.member_no})`
                    : "Belum dipilih"
                }}
              </div>
            </div>
            <div
              class="rounded-lg border border-zinc-200/70 bg-zinc-50/90 p-3 dark:border-zinc-800/70 dark:bg-zinc-950"
            >
              <div class="text-xs uppercase tracking-wide text-zinc-500">
                Jenis Simpanan
              </div>
              <div
                class="mt-1 text-sm font-medium text-zinc-950 dark:text-zinc-50"
              >
                {{ selectedContributionType?.name ?? "Belum dipilih" }}
              </div>
            </div>
            <div
              class="rounded-lg border border-zinc-200/70 bg-zinc-50/90 p-3 dark:border-zinc-800/70 dark:bg-zinc-950"
            >
              <div class="text-xs uppercase tracking-wide text-zinc-500">
                Nominal
              </div>
              <div
                class="mt-1 text-sm font-medium text-zinc-950 dark:text-zinc-50"
              >
                {{ form.amount ? formatCurrency(form.amount) : "-" }}
              </div>
            </div>
          </div>
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
                <th>Metode</th>
                <th class="text-right">Nominal</th>
                <th class="px-4 py-3">Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="payment in payments.data" :key="payment.id">
                <td class="px-4 py-3 whitespace-nowrap">
                  {{ formatDate(payment.paid_at) }}
                </td>
                <td class="px-4 py-3">
                  {{ payment.member?.member_no || "-" }}
                </td>
                <td class="font-medium text-zinc-950 dark:text-zinc-50">
                  {{ payment.member?.name || "-" }}
                </td>
                <td>
                  {{
                    payment.contribution_type?.name ||
                    payment.invoice?.contribution_type?.name ||
                    "-"
                  }}
                </td>
                <td>{{ payment.payment_method }}</td>
                <td class="text-right font-medium">
                  {{ formatCurrency(payment.amount) }}
                </td>
                <td class="px-4 py-3">
                  <div class="max-w-md whitespace-normal break-words">
                    {{ payment.notes || "-" }}
                  </div>
                </td>
              </tr>
              <tr v-if="payments.data.length === 0">
                <td colspan="7" class="px-4 py-10 text-center text-zinc-500">
                  Belum ada pembayaran yang tercatat.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
