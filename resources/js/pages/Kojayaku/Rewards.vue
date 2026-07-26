<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
  AlertTriangle,
  Award,
  Check,
  Coins,
  Gift,
  History,
  MapPin,
  PackageCheck,
  Sparkles,
  TrendingUp,
} from "lucide-vue-next";
import { computed } from "vue";
import StatusJourney from "@/components/Kojayaku/StatusJourney.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate } from "@/lib/formatters";

type Reward = {
  id: string;
  name: string;
  category: string;
  description?: string | null;
  points_required: number;
  stock?: number | null;
  valid_until?: string | null;
};

type Redemption = {
  id: string;
  redeemed_at: string;
  status: string;
  quantity: number;
  points_used: number;
  reward?: { name: string } | null;
};

const props = defineProps<{
  summary: {
    total_points: number;
    points_earned: number;
    points_redeemed: number;
    member_tier: string;
    next_tier: string | null;
    points_to_next_tier: number;
  };
  rewards: { data: Reward[] };
  redemptions: { data: Redemption[] };
  journey: {
    title: string;
    current_status: string;
    reference?: string | null;
    amount?: number | string | null;
    steps: Array<{
      label: string;
      completed: boolean;
      completed_at?: string | null;
    }>;
  };
}>();

const form = useForm({
  reward_id: props.rewards.data[0]?.id ?? "",
  quantity: 1,
  delivery_address: "",
});

const selectedReward = computed<Reward | null>(
  () =>
    props.rewards.data.find((reward) => reward.id === form.reward_id) ?? null,
);

const maxQuantity = computed<number>(() => {
  const stock = selectedReward.value?.stock;
  return stock != null && stock > 0 ? Math.min(stock, 20) : 20;
});

const pointsNeeded = computed<number>(
  () => (selectedReward.value?.points_required ?? 0) * (form.quantity || 0),
);

const hasEnoughPoints = computed<boolean>(
  () => props.summary.total_points >= pointsNeeded.value,
);

const remainingPoints = computed<number>(
  () => props.summary.total_points - pointsNeeded.value,
);

const canSubmit = computed<boolean>(
  () => Boolean(form.reward_id) && form.quantity >= 1 && hasEnoughPoints.value,
);

const selectReward = (reward: Reward): void => {
  if (reward.stock === 0) {
    return;
  }

  form.reward_id = reward.id;
  if (form.quantity > maxQuantity.value) {
    form.quantity = maxQuantity.value;
  }
};

const submit = (): void => {
  if (!form.reward_id || !canSubmit.value) {
    return;
  }

  router.post(`/member/rewards/${form.reward_id}/redeem`, {
    quantity: form.quantity,
    delivery_address: form.delivery_address,
  });
};

const tierThresholds: Record<string, number> = {
  BRONZE: 0,
  SILVER: 1000,
  GOLD: 2500,
  PLATINUM: 5000,
};

const tierProgress = computed<number>(() => {
  if (!props.summary.next_tier) {
    return 100;
  }

  const current = tierThresholds[props.summary.member_tier] ?? 0;
  const next = tierThresholds[props.summary.next_tier] ?? current;
  if (next <= current) {
    return 100;
  }

  return Math.min(
    100,
    Math.max(
      0,
      Math.round(
        ((props.summary.points_earned - current) / (next - current)) * 100,
      ),
    ),
  );
});

const statusMeta: Record<string, { label: string; classes: string }> = {
  PENDING: {
    label: "Menunggu",
    classes:
      "bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400",
  },
  PROCESSING: {
    label: "Diproses",
    classes: "bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400",
  },
  SHIPPED: {
    label: "Dikirim",
    classes:
      "bg-violet-100 text-violet-800 dark:bg-violet-500/10 dark:text-violet-400",
  },
  DELIVERED: {
    label: "Selesai",
    classes:
      "bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400",
  },
  CANCELLED: {
    label: "Dibatalkan",
    classes: "bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400",
  },
};

const statusBadge = (status: string): { label: string; classes: string } =>
  statusMeta[status] ?? {
    label: status,
    classes: "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300",
  };

const hasRedemptionJourney = computed<boolean>(
  () => props.journey.current_status !== "BELUM_ADA_PENUKARAN",
);
</script>

<template>
  <Head title="Reward" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Reward', href: '/member/rewards' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-3 sm:gap-5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-yellow-600 text-white shadow-lg shadow-amber-500/20 sm:h-16 sm:w-16"
          >
            <Gift class="h-6 w-6 sm:h-8 sm:w-8" />
          </div>
          <div>
            <h1
              class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl"
            >
              Reward &amp; Hadiah
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Tukarkan poin loyalitas Anda dengan beragam hadiah menarik dari
              koperasi.
            </p>
          </div>
        </header>

        <div
          class="rounded-3xl border border-amber-200/60 bg-gradient-to-br from-amber-500 to-yellow-600 p-4 text-white shadow-md shadow-amber-500/10 sm:p-6"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6"
          >
            <div>
              <span
                class="text-xs font-semibold uppercase tracking-wider text-amber-100"
                >Poin Tersedia</span
              >
              <div class="mt-1.5 flex items-end gap-2">
                <Coins class="mb-1.5 h-7 w-7 text-amber-200" />
                <span
                  class="text-3xl font-extrabold tracking-tight sm:text-4xl"
                  >{{ summary.total_points.toLocaleString("id-ID") }}</span
                >
                <span class="mb-1.5 text-sm font-semibold text-amber-100"
                  >poin</span
                >
              </div>
              <p class="mt-1 text-xs text-amber-100/90">
                Siap ditukar dengan hadiah pilihan Anda.
              </p>
            </div>
            <div
              class="flex items-center gap-3 rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-sm"
            >
              <div
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20"
              >
                <Award class="h-6 w-6" />
              </div>
              <div>
                <span
                  class="block text-[10px] font-bold uppercase tracking-wider text-amber-100"
                  >Tier Anda</span
                >
                <span class="text-lg font-extrabold uppercase tracking-tight">{{
                  summary.member_tier
                }}</span>
              </div>
            </div>
          </div>

          <div
            v-if="summary.next_tier"
            class="mt-5 border-t border-white/20 pt-4"
          >
            <div class="flex items-center justify-between text-xs">
              <span
                class="flex items-center gap-1.5 font-semibold text-amber-50"
              >
                <TrendingUp class="h-3.5 w-3.5" />
                Menuju tier {{ summary.next_tier }}
              </span>
              <span class="font-bold text-white"
                >{{ summary.points_to_next_tier.toLocaleString("id-ID") }} poin
                lagi</span
              >
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/25">
              <div
                class="h-full rounded-full bg-white transition-all duration-500"
                :style="{ width: `${tierProgress}%` }"
              />
            </div>
          </div>
        </div>

        <StatusJourney
          v-if="hasRedemptionJourney"
          :title="journey.title"
          :current-status="journey.current_status"
          :reference="journey.reference"
          :amount="journey.amount"
          :steps="journey.steps"
        />

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
          <section
            class="overflow-hidden rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm"
          >
            <div
              class="flex items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 px-4 py-4 sm:px-6 sm:py-5"
            >
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                >
                  <Sparkles class="h-5 w-5" />
                </div>
                <div>
                  <h2
                    class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white"
                  >
                    Katalog Reward
                  </h2>
                  <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Klik hadiah untuk memilih dan langsung menukarkan poin Anda.
                  </p>
                </div>
              </div>
            </div>

            <div class="grid gap-4 p-4 sm:gap-5 sm:p-6 sm:grid-cols-2">
              <div
                v-for="reward in rewards.data"
                :key="reward.id"
                role="button"
                tabindex="0"
                :aria-pressed="reward.id === form.reward_id"
                class="group relative flex cursor-pointer flex-col justify-between rounded-2xl border bg-white dark:bg-zinc-900 p-4 text-left shadow-sm outline-none transition-all duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-emerald-500/40 sm:p-5"
                :class="
                  reward.id === form.reward_id
                    ? 'border-emerald-500 dark:border-emerald-600 ring-2 ring-emerald-500/20 shadow-md'
                    : 'border-zinc-100 dark:border-zinc-800 hover:border-emerald-300 dark:hover:border-emerald-700/50'
                "
                @click="selectReward(reward)"
                @keydown.enter.prevent="selectReward(reward)"
                @keydown.space.prevent="selectReward(reward)"
              >
                <span
                  v-if="reward.id === form.reward_id"
                  class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm"
                >
                  <Check class="h-3.5 w-3.5" />
                </span>
                <div>
                  <div class="flex items-start justify-between gap-3">
                    <span
                      class="rounded-full border border-emerald-100 dark:border-emerald-950/30 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-800 dark:text-emerald-400"
                    >
                      {{ reward.category }}
                    </span>
                  </div>
                  <h3
                    class="mt-4 text-base font-bold leading-tight text-zinc-900 dark:text-white"
                  >
                    {{ reward.name }}
                  </h3>
                  <p
                    class="mt-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400 line-clamp-2"
                  >
                    {{ reward.description || "Tidak ada deskripsi tambahan." }}
                  </p>
                </div>

                <div class="mt-5 flex items-center justify-between">
                  <div
                    class="flex items-center gap-1.5 rounded-lg border border-amber-100 dark:border-amber-950/30 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 text-sm font-extrabold text-amber-700 dark:text-amber-400"
                  >
                    <Coins class="h-4 w-4" />
                    {{ reward.points_required.toLocaleString("id-ID") }}
                  </div>
                  <span
                    v-if="reward.stock === 0"
                    class="rounded-full bg-rose-100 dark:bg-rose-950/40 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-400 border border-rose-200/20 dark:border-rose-800/30"
                    >Stok Habis</span
                  >
                  <span
                    v-else-if="reward.stock != null && reward.stock <= 5"
                    class="rounded-full bg-amber-100 dark:bg-amber-950/40 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:text-amber-400 border border-amber-200/20 dark:border-amber-800/30"
                    >Sisa {{ reward.stock }}</span
                  >
                </div>
              </div>

              <div
                v-if="rewards.data.length === 0"
                class="col-span-full flex flex-col items-center justify-center gap-2 py-16 text-center text-zinc-500"
              >
                <Gift class="h-10 w-10 text-zinc-300 dark:text-zinc-700" />
                <p class="text-sm font-medium">
                  Belum ada reward terdaftar di katalog.
                </p>
              </div>
            </div>
          </section>

          <div class="flex flex-col gap-6">
            <section
              class="rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm sm:p-6"
            >
              <h2
                class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white"
              >
                Tukar Poin
              </h2>
              <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                Pilih hadiah dan masukkan jumlah klaim Anda.
              </p>

              <div class="mt-6 space-y-4">
                <div class="space-y-1.5">
                  <label
                    for="member-reward-id"
                    class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >Pilih Reward</label
                  >
                  <select
                    id="member-reward-id"
                    v-model="form.reward_id"
                    class="h-10 w-full rounded-xl border border-zinc-200 dark:border-zinc-800 bg-background px-3 text-sm outline-none transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:text-white"
                  >
                    <option value="" disabled>Pilih hadiah…</option>
                    <option
                      v-for="reward in rewards.data"
                      :key="reward.id"
                      :value="reward.id"
                    >
                      {{ reward.name }} · {{ reward.points_required }} poin
                    </option>
                  </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="space-y-1.5">
                    <label
                      class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                      >Jumlah</label
                    >
                    <Input
                      v-model.number="form.quantity"
                      type="number"
                      :min="1"
                      :max="maxQuantity"
                      placeholder="Jumlah"
                      class="rounded-xl dark:border-zinc-800"
                    />
                  </div>
                  <div class="space-y-1.5">
                    <label
                      class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                      >Stok Tersedia</label
                    >
                    <div
                      class="flex h-10 items-center rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 px-3 text-sm font-semibold text-zinc-600 dark:text-zinc-300"
                    >
                      {{
                        selectedReward?.stock != null
                          ? selectedReward.stock
                          : "Tanpa batas"
                      }}
                    </div>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label
                    class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                  >
                    <MapPin class="h-3.5 w-3.5" />
                    Alamat Pengiriman
                  </label>
                  <Textarea
                    v-model="form.delivery_address"
                    :rows="3"
                    placeholder="Tuliskan alamat pengiriman lengkap Anda..."
                    class="rounded-xl dark:border-zinc-800"
                  />
                </div>

                <div
                  v-if="selectedReward"
                  class="rounded-2xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/35 p-4"
                >
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400"
                      >{{
                        selectedReward.points_required.toLocaleString("id-ID")
                      }}
                      poin × {{ form.quantity || 0 }}</span
                    >
                    <span class="font-bold text-zinc-900 dark:text-white"
                      >{{ pointsNeeded.toLocaleString("id-ID") }} poin</span
                    >
                  </div>
                  <div
                    class="mt-2 flex items-center justify-between border-t border-zinc-200/70 dark:border-zinc-800 pt-2 text-sm"
                  >
                    <span class="font-semibold text-zinc-600 dark:text-zinc-400"
                      >Sisa poin setelah penukaran</span
                    >
                    <span
                      class="font-extrabold"
                      :class="
                        hasEnoughPoints
                          ? 'text-emerald-700 dark:text-emerald-400'
                          : 'text-rose-600 dark:text-rose-400'
                      "
                      >{{ remainingPoints.toLocaleString("id-ID") }}</span
                    >
                  </div>
                  <div
                    v-if="!hasEnoughPoints"
                    class="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 px-3 py-2 text-xs font-semibold text-rose-700 dark:text-rose-400 border border-rose-200/20 dark:border-rose-800/30"
                  >
                    <AlertTriangle class="h-4 w-4 shrink-0" />
                    Poin Anda tidak mencukupi untuk penukaran ini.
                  </div>
                </div>

                <Button
                  class="w-full rounded-xl py-6 text-sm font-bold uppercase tracking-wider"
                  :disabled="!canSubmit"
                  @click="submit"
                >
                  <PackageCheck class="h-4 w-4" />
                  Tukar Sekarang
                </Button>
              </div>
            </section>

            <section
              class="flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm"
            >
              <div>
                <div
                  class="flex items-center gap-4 border-b border-zinc-100 dark:border-zinc-800 p-4 sm:p-6"
                >
                  <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700 shadow-sm dark:bg-violet-500/10 dark:text-violet-400"
                  >
                    <History class="h-5 w-5" />
                  </div>
                  <div>
                    <h2
                      class="font-bold tracking-tight text-zinc-900 dark:text-white"
                    >
                      Riwayat Klaim
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                      Status penukaran reward Anda sebelumnya.
                    </p>
                  </div>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-sm">
                    <thead
                      class="bg-zinc-50/50 dark:bg-zinc-800/50 text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >
                      <tr>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Hadiah</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Tanggal</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Status</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4 text-right">
                          Poin
                        </th>
                      </tr>
                    </thead>
                    <tbody
                      class="divide-y divide-zinc-50 dark:divide-zinc-800/50"
                    >
                      <tr
                        v-for="redemption in redemptions.data"
                        :key="redemption.id"
                        class="border-t border-zinc-50 dark:border-zinc-800 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                      >
                        <td
                          class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                          {{ redemption.reward?.name || "-" }}
                        </td>
                        <td
                          class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                          {{ formatDate(redemption.redeemed_at) }}
                        </td>
                        <td class="px-4 py-3 sm:px-6 sm:py-4">
                          <span
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide"
                            :class="statusBadge(redemption.status).classes"
                          >
                            {{ statusBadge(redemption.status).label }}
                          </span>
                        </td>
                        <td
                          class="px-4 py-3 sm:px-6 sm:py-4 text-right text-xs font-extrabold text-zinc-800 dark:text-zinc-200"
                        >
                          {{ redemption.points_used.toLocaleString("id-ID") }}
                        </td>
                      </tr>
                      <tr v-if="redemptions.data.length === 0">
                        <td
                          colspan="4"
                          class="px-6 py-12 text-center text-zinc-500"
                        >
                          Belum ada riwayat klaim reward.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
