<script setup lang="ts">
import { Deferred, Head, router } from "@inertiajs/vue3";
import { FileSpreadsheet, FileText } from "lucide-vue-next";
import { computed, ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import JobProgressTracker from "@/components/JobProgressTracker.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import { useBackgroundJob } from "@/composables/useBackgroundJob";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

interface AnalyticsData {
  summary: {
    transactions: number;
    voided_transactions: number;
    gross_sales: number;
    total_discount: number;
    gross_profit: number;
    voided_amount: number;
    returns: { count: number; total: number };
    net_sales: number;
    member_transactions: number;
  };
  payment_reconciliation: Array<{
    method: string;
    count: number;
    total: number;
  }>;
  daily_trend: Array<{ date: string; transactions: number; revenue: number }>;
  top_products: Array<{
    product_name: string;
    quantity: number;
    revenue: number;
    gross_profit: number;
    margin_percent: number;
  }>;
  top_members: Array<{
    member_name: string;
    transactions: number;
    total: number;
  }>;
  cashier_performance: Array<{
    cashier_name: string;
    transactions: number;
    total: number;
  }>;
}

interface FilterValues {
  pos_product_id: string;
  category_id: string;
  cashier_id: string;
  payment_method: string;
}

const props = defineProps<{
  from: string;
  to: string;
  analytics?: AnalyticsData;
  filters: FilterValues;
  products: Array<{ id: number; name: string }>;
  categories: Array<{ id: number; name: string }>;
  cashiers: Array<{ id: number; name: string }>;
}>();

function cleanFilters(filters: Record<string, string>): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== "" && v !== undefined),
  );
}

const f = ref<FilterValues>({
  pos_product_id: props.filters.pos_product_id ?? "",
  category_id: props.filters.category_id ?? "",
  cashier_id: props.filters.cashier_id ?? "",
  payment_method: props.filters.payment_method ?? "",
});

const dateFilter = ref({
  from: props.from,
  to: props.to,
});

function applyFilters(): void {
  router.get(
    "/cooperative/pos/reports",
    { ...cleanFilters(f.value), ...dateFilter.value },
    { preserveState: true },
  );
}

const exportQuery = computed(() => {
  const params = new URLSearchParams();
  if (dateFilter.value.from) {
    params.set("from", dateFilter.value.from);
  }
  if (dateFilter.value.to) {
    params.set("to", dateFilter.value.to);
  }
  for (const [key, value] of Object.entries(cleanFilters(f.value))) {
    params.set(key, value);
  }
  return params.toString();
});

const csvHref = computed(
  () =>
    `/cooperative/pos/reports/export.csv${exportQuery.value ? `?${exportQuery.value}` : ""}`,
);

const pdfJob = useBackgroundJob();

function buildPdfPayload(): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    from: dateFilter.value.from,
    to: dateFilter.value.to,
  };
  const cleaned = cleanFilters(f.value);
  if (Object.keys(cleaned).length > 0) {
    payload.filters = cleaned;
  }
  return payload;
}

function generatePdf(): void {
  void pdfJob.enqueue(buildPdfPayload());
}

function retryPdf(): void {
  void pdfJob.enqueue(buildPdfPayload());
}

function dismissPdf(): void {
  pdfJob.reset();
}

const maxRevenue = computed(() =>
  Math.max(1, ...(props.analytics?.daily_trend ?? []).map((d) => d.revenue)),
);
</script>
<template>
  <Head title="Laporan POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '/cooperative/pos' },
      { title: 'Laporan', href: '/cooperative/pos/reports' },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Laporan POS"
        description="Pantau penjualan, laba, rekonsiliasi pembayaran, dan tren pada periode yang dipilih."
      >
        <Button as-child variant="outline" size="sm"
          ><a :href="csvHref"><FileSpreadsheet class="size-4" /> CSV</a></Button
        >
        <Button
          variant="outline"
          size="sm"
          :disabled="pdfJob.submitting.value"
          @click="generatePdf"
          ><FileText class="size-4" /> Buat PDF</Button
        >
      </WorkspaceHeader>
      <JobProgressTracker
        v-if="pdfJob.hasJobStarted.value"
        :state="pdfJob.state.value"
        :submitting="pdfJob.submitting.value"
        :error="pdfJob.error.value"
        label="Laporan POS · PDF"
        @retry="retryPdf"
        @reset="dismissPdf"
      />
      <form
        class="grid items-end gap-2 rounded-xl border p-3 sm:grid-cols-2 xl:grid-cols-[repeat(6,minmax(0,1fr))_auto]"
        @submit.prevent="applyFilters"
      >
        <div class="space-y-1">
          <Label for="report-from" class="text-xs">Dari</Label
          ><Input id="report-from" v-model="dateFilter.from" type="date" />
        </div>
        <div class="space-y-1">
          <Label for="report-to" class="text-xs">Sampai</Label
          ><Input id="report-to" v-model="dateFilter.to" type="date" />
        </div>
        <div class="min-w-0 space-y-1">
          <Label for="report-pos_product_id" class="text-xs">Produk</Label
          ><select
            id="report-pos_product_id"
            v-model="f.pos_product_id"
            class="h-9 w-full rounded-md border bg-transparent px-2 text-sm"
          >
            <option value="">Semua produk</option>
            <option
              v-for="option in products"
              :key="option.id"
              :value="option.id"
            >
              {{ option.name }}
            </option>
          </select>
        </div>
        <div class="min-w-0 space-y-1">
          <Label for="report-category_id" class="text-xs">Kategori</Label
          ><select
            id="report-category_id"
            v-model="f.category_id"
            class="h-9 w-full rounded-md border bg-transparent px-2 text-sm"
          >
            <option value="">Semua kategori</option>
            <option
              v-for="option in categories"
              :key="option.id"
              :value="option.id"
            >
              {{ option.name }}
            </option>
          </select>
        </div>
        <div class="min-w-0 space-y-1">
          <Label for="report-cashier_id" class="text-xs">Kasir</Label
          ><select
            id="report-cashier_id"
            v-model="f.cashier_id"
            class="h-9 w-full rounded-md border bg-transparent px-2 text-sm"
          >
            <option value="">Semua kasir</option>
            <option
              v-for="option in cashiers"
              :key="option.id"
              :value="option.id"
            >
              {{ option.name }}
            </option>
          </select>
        </div>

        <div class="space-y-1">
          <Label for="report-method" class="text-xs">Metode</Label
          ><select
            id="report-method"
            v-model="f.payment_method"
            class="h-9 w-full rounded-md border bg-transparent px-2 text-sm"
          >
            <option value="">Semua metode</option>
            <option value="CASH">Tunai</option>
            <option value="TRANSFER">Transfer</option>
            <option value="QRIS">QRIS</option>
            <option value="MEMBER_CREDIT">Kredit Anggota</option>
          </select>
        </div>
        <Button type="submit" size="sm">Terapkan</Button>
      </form>
      <Deferred data="analytics">
        <template #fallback
          ><div role="status" class="space-y-4">
            <span class="sr-only">Memuat laporan POS.</span
            ><Skeleton class="h-[84px] rounded-xl" />
            <div class="grid gap-4 md:grid-cols-2">
              <Skeleton class="h-44 rounded-xl" /><Skeleton
                class="h-44 rounded-xl"
              />
            </div></div
        ></template>
        <template v-if="analytics">
          <WorkspaceSummary
            :items="[
              {
                label: 'Penjualan Kotor',
                value: formatCurrency(analytics.summary.gross_sales),
              },
              {
                label: 'Laba Kotor',
                value: formatCurrency(analytics.summary.gross_profit),
                tone: 'emerald',
              },
              {
                label: 'Penjualan Bersih',
                value: formatCurrency(analytics.summary.net_sales),
              },
              {
                label: 'Transaksi',
                value: analytics.summary.transactions,
                hint: `${analytics.summary.voided_transactions} void · ${analytics.summary.returns.count} retur`,
              },
            ]"
          />
          <section
            class="min-w-0 overflow-hidden rounded-xl border bg-white dark:bg-zinc-900"
            aria-label="Analisis penjualan POS"
          >
            <div
              class="grid divide-y border-b lg:grid-cols-2 lg:divide-x lg:divide-y-0"
            >
              <section class="min-w-0 p-4">
                <h2 class="text-sm font-semibold">Tren Harian</h2>
                <p
                  v-if="analytics.daily_trend.length === 0"
                  class="py-8 text-center text-sm text-zinc-500"
                >
                  Belum ada transaksi POS pada periode ini.
                </p>
                <div v-else class="mt-4 space-y-3">
                  <div
                    v-for="day in analytics.daily_trend"
                    :key="day.date"
                    class="flex min-w-0 items-center gap-3"
                  >
                    <span class="w-20 shrink-0 text-xs text-zinc-500">{{
                      day.date
                    }}</span>
                    <div
                      class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                    >
                      <div
                        class="h-full rounded-full bg-emerald-600"
                        :style="{
                          width: (day.revenue / maxRevenue) * 100 + '%',
                        }"
                      ></div>
                    </div>
                    <span
                      class="shrink-0 whitespace-nowrap text-right text-xs font-semibold tabular-nums"
                      >{{ formatCurrency(day.revenue) }}</span
                    >
                  </div>
                </div>
              </section>
              <section class="min-w-0 p-4">
                <h2 class="text-sm font-semibold">Rekonsiliasi Pembayaran</h2>
                <div
                  class="mt-3 overflow-x-auto"
                  role="region"
                  aria-label="Rekonsiliasi pembayaran, geser untuk melihat kolom lainnya"
                  tabindex="0"
                >
                  <table
                    class="w-full min-w-[360px] text-left text-sm [&_td]:py-2.5 [&_td]:whitespace-nowrap"
                  >
                    <thead class="border-b text-xs text-zinc-500">
                      <tr>
                        <th class="py-2">Metode</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="row in analytics.payment_reconciliation"
                        :key="row.method"
                        class="border-b last:border-0"
                      >
                        <td>{{ row.method }}</td>
                        <td class="text-right tabular-nums">{{ row.count }}</td>
                        <td class="text-right font-semibold tabular-nums">
                          {{ formatCurrency(row.total) }}
                        </td>
                      </tr>
                      <tr v-if="analytics.payment_reconciliation.length === 0">
                        <td
                          colspan="3"
                          class="text-center text-xs text-zinc-500"
                        >
                          Belum ada pembayaran pada periode ini.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
            <section class="min-w-0 border-b p-4">
              <h2 class="text-sm font-semibold">Top 20 Produk</h2>
              <div
                class="mt-3 overflow-x-auto"
                role="region"
                aria-label="Produk terlaris, geser untuk melihat kolom lainnya"
                tabindex="0"
              >
                <table
                  class="w-full min-w-[700px] text-left text-sm [&_td]:py-2.5 [&_td]:pr-4 [&_th]:pr-4"
                >
                  <thead class="border-b text-xs text-zinc-500">
                    <tr>
                      <th class="py-2">Produk</th>
                      <th class="text-right">Qty</th>
                      <th class="text-right">Pendapatan</th>
                      <th class="text-right">Laba</th>
                      <th class="text-right">Margin</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(product, i) in analytics.top_products"
                      :key="i"
                      class="border-b last:border-0"
                    >
                      <td class="min-w-[200px] font-medium">
                        {{ product.product_name }}
                      </td>
                      <td class="text-right tabular-nums">
                        {{ product.quantity }}
                      </td>
                      <td class="whitespace-nowrap text-right tabular-nums">
                        {{ formatCurrency(product.revenue) }}
                      </td>
                      <td
                        class="whitespace-nowrap text-right tabular-nums text-emerald-700 dark:text-emerald-400"
                      >
                        {{ formatCurrency(product.gross_profit) }}
                      </td>
                      <td class="text-right tabular-nums">
                        {{ product.margin_percent }}%
                      </td>
                    </tr>
                    <tr v-if="analytics.top_products.length === 0">
                      <td
                        colspan="5"
                        class="py-6 text-center text-xs text-zinc-500"
                      >
                        Belum ada produk terjual pada periode ini.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
            <div class="grid divide-y lg:grid-cols-2 lg:divide-x lg:divide-y-0">
              <section class="min-w-0 p-4">
                <h2 class="text-sm font-semibold">Top Anggota</h2>
                <ul class="mt-3 divide-y">
                  <li
                    v-for="member in analytics.top_members"
                    :key="member.member_name"
                    class="flex items-start justify-between gap-3 py-2.5 text-sm"
                  >
                    <span class="min-w-0 break-words font-medium">{{
                      member.member_name
                    }}</span
                    ><span class="shrink-0 text-right text-xs text-zinc-500"
                      >{{ member.transactions }} trx<br /><span
                        class="whitespace-nowrap tabular-nums"
                        >{{ formatCurrency(member.total) }}</span
                      ></span
                    >
                  </li>
                  <li
                    v-if="analytics.top_members.length === 0"
                    class="py-4 text-center text-xs text-zinc-500"
                  >
                    Belum ada transaksi anggota.
                  </li>
                </ul>
              </section>
              <section class="min-w-0 p-4">
                <h2 class="text-sm font-semibold">Performa Kasir</h2>
                <ul class="mt-3 divide-y">
                  <li
                    v-for="cashier in analytics.cashier_performance"
                    :key="cashier.cashier_name"
                    class="flex items-start justify-between gap-3 py-2.5 text-sm"
                  >
                    <span class="min-w-0 break-words font-medium">{{
                      cashier.cashier_name
                    }}</span
                    ><span class="shrink-0 text-right text-xs text-zinc-500"
                      >{{ cashier.transactions }} trx<br /><span
                        class="whitespace-nowrap tabular-nums"
                        >{{ formatCurrency(cashier.total) }}</span
                      ></span
                    >
                  </li>
                  <li
                    v-if="analytics.cashier_performance.length === 0"
                    class="py-4 text-center text-xs text-zinc-500"
                  >
                    Belum ada transaksi kasir.
                  </li>
                </ul>
              </section>
            </div>
          </section>
        </template>
      </Deferred>
    </PageContainer>
  </AppLayout>
</template>
