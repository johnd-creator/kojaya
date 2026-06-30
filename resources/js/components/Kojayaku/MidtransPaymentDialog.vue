<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import axios from "axios";
import {
  AlertCircle,
  ArrowLeft,
  CheckCircle2,
  Clock,
  Copy,
  CreditCard,
  ExternalLink,
  Loader2,
  QrCode,
  RefreshCw,
  Wallet,
} from "lucide-vue-next";
import QRCode from "qrcode";
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { formatCurrency } from "@/lib/formatters";

type Channel = "QRIS" | "VA" | "E_WALLET";

type InvoiceForDialog = {
  id: number;
  amount: number;
  paid_amount: number;
  due_date: string | null;
};

type Charge = {
  payment_id: number;
  channel: Channel;
  provider: string;
  qr_string: string | null;
  checkout_url: string | null;
  instructions:
    | {
        bank?: string;
        va_number?: string;
      }
    | Record<string, never>;
  gateway_reference: string;
  expires_at: string | null;
  amount: number;
  requested_channel?: Channel;
  fallback_reason?: string;
};

const props = defineProps<{
  open: boolean;
  invoice: InvoiceForDialog | null;
}>();

const emit = defineEmits<{
  "update:open": [value: boolean];
}>();

type Phase = "select" | "creating" | "show_payment" | "success" | "error";

type PaymentStatus = {
  is_paid: boolean;
  is_failed: boolean;
  is_terminal: boolean;
  gateway_expires_at: string | null;
};

const POLL_INTERVAL_MS = 3000;
const MAX_POLL_DURATION_MS = 30 * 60 * 1000; // 30 min safety net when no expiry signalled

const phase = ref<Phase>("select");
const channel = ref<Channel>("QRIS");
const charge = ref<Charge | null>(null);
const errorMessage = ref<string>("");
const copied = ref(false);

const qrCanvas = ref<HTMLCanvasElement | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;
let countdownTimer: ReturnType<typeof setInterval> | null = null;
let pollDeadline = 0;
const nowTick = ref(Date.now());

const remainingAmount = computed(() => {
  if (!props.invoice) return 0;
  return Math.max(props.invoice.amount - props.invoice.paid_amount, 0);
});

const expiryTimestamp = computed(() => {
  const raw = charge.value?.expires_at;
  if (!raw) return null;
  const ms = new Date(raw).getTime();
  return Number.isNaN(ms) ? null : ms;
});

const remainingSeconds = computed(() => {
  if (expiryTimestamp.value === null) return null;
  const diff = Math.floor((expiryTimestamp.value - nowTick.value) / 1000);
  return diff > 0 ? diff : 0;
});

const isChargeExpired = computed(
  () => remainingSeconds.value !== null && remainingSeconds.value === 0,
);

const expiryLabel = computed(() => {
  const s = remainingSeconds.value;
  if (s === null) return null;
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = s % 60;
  const pad = (n: number) => n.toString().padStart(2, "0");
  return h > 0 ? `${pad(h)}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
});

const vaNumber = computed(() => charge.value?.instructions?.va_number ?? null);
const bankName = computed(() => charge.value?.instructions?.bank ?? null);
const activeChannel = computed(() => charge.value?.channel ?? channel.value);
const usedFallbackChannel = computed(
  () =>
    charge.value?.fallback_reason === "MIDTRANS_CHANNEL_INACTIVE" &&
    charge.value.requested_channel &&
    charge.value.requested_channel !== charge.value.channel,
);
const isDevFallback = computed(
  () =>
    charge.value?.provider === "internal" &&
    !charge.value.qr_string &&
    !charge.value.checkout_url,
);

const channels: Array<{
  code: Channel;
  label: string;
  desc: string;
  icon: typeof QrCode;
}> = [
  {
    code: "QRIS",
    label: "QRIS",
    desc: "Scan pakai e-wallet / m-banking apapun",
    icon: QrCode,
  },
  {
    code: "VA",
    label: "Virtual Account",
    desc: "Transfer via nomor rekening virtual",
    icon: CreditCard,
  },
  {
    code: "E_WALLET",
    label: "E-Wallet",
    desc: "GoPay, DANA, ShopeePay",
    icon: Wallet,
  },
];

async function startPayment(): Promise<void> {
  if (!props.invoice) return;
  phase.value = "creating";
  errorMessage.value = "";

  try {
    const { data } = await axios.post("/member/payments/intent", {
      cooperative_dues_invoice_id: props.invoice.id,
      channel: channel.value,
    });

    const created: Charge = data.data;
    charge.value = created;
    channel.value = created.channel;
    phase.value = "show_payment";

    if (created.channel === "QRIS" && created.qr_string) {
      await nextTick();
      await renderQr(created.qr_string);
    }

    startPolling(created.payment_id);
  } catch (e) {
    phase.value = "error";
    errorMessage.value =
      (e as { response?: { data?: { message?: string } } })?.response?.data
        ?.message ??
      "Terjadi kesalahan saat membuat transaksi. Silakan coba lagi.";
  }
}

async function renderQr(text: string): Promise<void> {
  if (!qrCanvas.value) return;
  await QRCode.toCanvas(qrCanvas.value, text, {
    width: 220,
    margin: 1,
    color: { dark: "#0f172a", light: "#ffffff" },
  });
}

function startPolling(paymentId: number): void {
  stopPolling();
  pollDeadline = Date.now() + MAX_POLL_DURATION_MS;
  pollTimer = setInterval(async () => {
    if (Date.now() > pollDeadline) {
      stopPolling();
      failWithExpiryOrGeneric();
      return;
    }
    try {
      const { data } = await axios.get(`/member/payments/${paymentId}/status`);
      const status = data.data as PaymentStatus;
      if (status.is_paid) {
        stopPolling();
        phase.value = "success";
        setTimeout(() => router.reload(), 1500);
      } else if (status.is_failed) {
        stopPolling();
        phase.value = "error";
        errorMessage.value =
          "Pembayaran gagal, dibatalkan, atau sudah kedaluwarsa. Silakan coba metode lain.";
      }
    } catch {
      // keep polling; settlement may take a moment
    }
  }, POLL_INTERVAL_MS);

  startCountdown();
}

function startCountdown(): void {
  stopCountdown();
  countdownTimer = setInterval(() => {
    nowTick.value = Date.now();
    if (isChargeExpired.value && phase.value === "show_payment") {
      stopPolling();
      failWithExpiryOrGeneric();
    }
  }, 1000);
}

function failWithExpiryOrGeneric(): void {
  phase.value = "error";
  errorMessage.value =
    "Waktu pembayaran telah habis. Silakan mulai ulang transaksi.";
}

function stopPolling(): void {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
  stopCountdown();
}

function stopCountdown(): void {
  if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
}

function changeMethod(): void {
  stopPolling();
  charge.value = null;
  phase.value = "select";
}

function resetState(): void {
  stopPolling();
  phase.value = "select";
  channel.value = "QRIS";
  charge.value = null;
  errorMessage.value = "";
  copied.value = false;
  pollDeadline = 0;
  nowTick.value = Date.now();
}

function handleOpenChange(value: boolean): void {
  emit("update:open", value);
  if (!value) {
    resetState();
  }
}

function copyVa(): void {
  if (vaNumber.value) {
    navigator.clipboard?.writeText(vaNumber.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      resetState();
    }
  },
);

onBeforeUnmount(() => stopPolling());

const summary = computed(() => [
  { label: "Total Tagihan", value: formatCurrency(props.invoice?.amount ?? 0) },
  {
    label: "Sudah Dibayar",
    value: formatCurrency(props.invoice?.paid_amount ?? 0),
  },
  {
    label: "Sisa Tagihan",
    value: formatCurrency(remainingAmount.value),
    highlight: true,
  },
]);
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="max-w-md max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Wallet class="h-5 w-5 text-emerald-600" />
          Bayar Tagihan
        </DialogTitle>
        <DialogDescription>
          Bayar aman secara realtime melalui Midtrans.
        </DialogDescription>
      </DialogHeader>

      <div v-if="invoice" class="space-y-4">
        <!-- Summary -->
        <div class="rounded-xl border bg-muted/40 p-4 space-y-2">
          <div
            v-for="row in summary"
            :key="row.label"
            class="flex justify-between text-sm"
            :class="row.highlight ? 'pt-2 border-t border-dashed' : ''"
          >
            <span class="text-muted-foreground">{{ row.label }}</span>
            <span
              :class="
                row.highlight
                  ? 'font-bold text-emerald-700 dark:text-emerald-400 text-base'
                  : 'font-medium'
              "
              >{{ row.value }}</span
            >
          </div>
        </div>

        <!-- Phase: select method -->
        <div v-if="phase === 'select'" class="space-y-2.5">
          <p
            class="text-xs font-semibold text-muted-foreground uppercase tracking-wide"
          >
            Pilih Metode Pembayaran
          </p>
          <button
            v-for="opt in channels"
            :key="opt.code"
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition-all hover:border-emerald-400 hover:bg-emerald-50/40 dark:hover:bg-emerald-500/5"
            :class="
              channel === opt.code
                ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-500/10 ring-1 ring-emerald-500'
                : 'border-zinc-200 dark:border-zinc-800'
            "
            @click="channel = opt.code"
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
              :class="
                channel === opt.code
                  ? 'bg-emerald-600 text-white'
                  : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300'
              "
            >
              <component :is="opt.icon" class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
              <span
                class="block text-sm font-bold text-zinc-900 dark:text-white"
                >{{ opt.label }}</span
              >
              <span class="block text-[11px] text-muted-foreground">{{
                opt.desc
              }}</span>
            </span>
            <span
              v-if="channel === opt.code"
              class="h-2.5 w-2.5 rounded-full bg-emerald-600"
            />
          </button>
        </div>

        <!-- Phase: creating -->
        <div
          v-else-if="phase === 'creating'"
          class="flex flex-col items-center justify-center py-10 gap-3"
        >
          <Loader2 class="h-8 w-8 animate-spin text-emerald-600" />
          <p class="text-sm text-muted-foreground">Menyiapkan pembayaran...</p>
        </div>

        <!-- Phase: show_payment -->
        <div v-else-if="phase === 'show_payment' && charge" class="space-y-4">
          <div
            v-if="usedFallbackChannel"
            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
          >
            Kanal yang dipilih belum aktif di sandbox Midtrans. Pembayaran
            otomatis dialihkan ke Virtual Account.
          </div>

          <!-- QRIS -->
          <div
            v-if="activeChannel === 'QRIS'"
            class="flex flex-col items-center gap-3"
          >
            <div
              v-if="charge.qr_string"
              class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white p-3 shadow-sm"
            >
              <canvas ref="qrCanvas"></canvas>
            </div>
            <div
              v-else
              class="flex flex-col items-center gap-2 py-6 text-center"
            >
              <QrCode class="h-10 w-10 text-zinc-300" />
              <p class="text-xs text-muted-foreground max-w-[16rem]">
                QR pembayaran belum tersedia
                {{ isDevFallback ? "(mode dev tanpa Midtrans)" : "" }}.
              </p>
            </div>
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
              Scan QR di atas untuk membayar
            </p>
            <p class="text-[11px] text-muted-foreground">
              Buka e-wallet / m-banking apapun lalu scan kode QRIS ini.
            </p>
          </div>

          <!-- VA -->
          <div v-else-if="activeChannel === 'VA'" class="space-y-3">
            <div
              v-if="vaNumber"
              class="rounded-xl border bg-muted/40 p-4 space-y-3"
            >
              <div>
                <p
                  class="text-[11px] uppercase tracking-wide text-muted-foreground"
                >
                  Bank
                </p>
                <p class="font-bold text-zinc-900 dark:text-white">
                  {{ bankName || "Virtual Account" }}
                </p>
              </div>
              <div class="border-t border-dashed pt-3">
                <p
                  class="text-[11px] uppercase tracking-wide text-muted-foreground"
                >
                  Nomor Virtual Account
                </p>
                <div class="mt-1 flex items-center justify-between gap-2">
                  <p
                    class="font-mono font-bold text-lg text-zinc-900 dark:text-white break-all"
                  >
                    {{ vaNumber }}
                  </p>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    class="shrink-0"
                    @click="copyVa"
                  >
                    <Copy class="h-3.5 w-3.5" />
                    {{ copied ? "Disalin" : "Salin" }}
                  </Button>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <p class="text-xs text-muted-foreground">
                Nomor VA belum tersedia
                {{ isDevFallback ? "(mode dev tanpa Midtrans)" : "" }}.
              </p>
            </div>
            <p class="text-[11px] text-muted-foreground text-center">
              Transfer tepat sebesar tagihan ke nomor VA di atas via ATM /
              m-banking / teller.
            </p>
          </div>

          <!-- E-Wallet -->
          <div
            v-else-if="activeChannel === 'E_WALLET'"
            class="flex flex-col items-center gap-3 py-2"
          >
            <div
              class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600"
            >
              <Wallet class="h-7 w-7" />
            </div>
            <p
              class="text-sm font-semibold text-zinc-900 dark:text-white text-center"
            >
              Bayar dengan E-Wallet
            </p>
            <Button
              v-if="charge.checkout_url"
              type="button"
              class="bg-emerald-600 hover:bg-emerald-700"
              as="a"
              :href="charge.checkout_url"
              target="_blank"
              rel="noopener"
            >
              <ExternalLink class="mr-2 h-4 w-4" />
              Lanjut ke E-Wallet
            </Button>
            <p v-else class="text-xs text-muted-foreground text-center">
              Tautan e-wallet belum tersedia
              {{ isDevFallback ? "(mode dev tanpa Midtrans)" : "" }}.
            </p>
          </div>

          <!-- Polling indicator -->
          <div
            class="flex items-center justify-between gap-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 p-2.5 text-xs text-amber-800 dark:text-amber-400"
          >
            <span class="flex items-center gap-2">
              <Loader2 class="h-3.5 w-3.5 animate-spin" />
              Menunggu konfirmasi...
            </span>
            <span
              v-if="expiryLabel"
              class="flex items-center gap-1 font-mono font-semibold tabular-nums"
            >
              <Clock class="h-3.5 w-3.5" />
              {{ expiryLabel }}
            </span>
          </div>

          <button
            type="button"
            class="flex items-center justify-center gap-1.5 w-full text-xs font-semibold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-300"
            @click="changeMethod"
          >
            <ArrowLeft class="h-3.5 w-3.5" />
            Ganti metode pembayaran
          </button>
        </div>

        <!-- Phase: success -->
        <div
          v-else-if="phase === 'success'"
          class="flex flex-col items-center justify-center py-10 gap-3 text-center"
        >
          <div
            class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-500/10"
          >
            <CheckCircle2 class="h-8 w-8 text-emerald-600" />
          </div>
          <div>
            <p class="font-bold text-zinc-900 dark:text-white">
              Pembayaran Berhasil!
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
              Tagihan Anda telah terbayar. Memuat ulang halaman...
            </p>
          </div>
        </div>

        <!-- Phase: error -->
        <div
          v-else-if="phase === 'error'"
          class="flex flex-col items-center justify-center py-6 gap-3 text-center"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-500/10"
          >
            <AlertCircle class="h-6 w-6 text-rose-600" />
          </div>
          <p class="text-sm text-rose-700 dark:text-rose-400 max-w-xs">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-2">
        <Button
          type="button"
          variant="outline"
          @click="emit('update:open', false)"
          >{{
            phase === "show_payment" || phase === "success" ? "Tutup" : "Batal"
          }}</Button
        >
        <Button
          v-if="phase === 'select'"
          type="button"
          :disabled="remainingAmount <= 0"
          class="bg-emerald-600 hover:bg-emerald-700"
          @click="startPayment"
        >
          <Wallet class="mr-2 h-4 w-4" />
          Lanjutkan Pembayaran
        </Button>
        <Button
          v-else-if="phase === 'error'"
          type="button"
          variant="secondary"
          @click="resetState"
        >
          <RefreshCw class="mr-2 h-4 w-4" />
          Coba Lagi
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
