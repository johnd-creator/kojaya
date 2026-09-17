<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import {
  AlertCircle,
  Building,
  CheckCircle2,
  Clock,
  FileText,
  Mail,
  ShieldCheck,
  Sparkles,
  User,
  XCircle,
} from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import AppLayout from "@/layouts/AppLayout.vue";

interface MemberData {
  id?: number;
  member_no?: string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  status?: string | null;
  validation_status?: string | null;
  validation_notes?: string | null;
  organization?: { name?: string } | null;
  user?: { name?: string; email?: string } | null;
}

const props = defineProps<{
  member: MemberData;
  lifecycle_experience?: string;
  review_state?: string;
  validation_status?: string;
  validation_notes?: string | null;
  submitted?: boolean;
}>();

const isWaitingVerification = computed(
  () => props.lifecycle_experience === "WAITING_VERIFICATION",
);
const isUnderReview = computed(
  () => props.lifecycle_experience === "UNDER_REVIEW",
);
const isRevisionRequired = computed(
  () => props.lifecycle_experience === "REVISION_REQUIRED",
);
const isRejected = computed(() => props.lifecycle_experience === "REJECTED");

const statusMeta = computed(() => {
  if (isRejected.value) {
    return {
      tone: "destructive" as const,
      title: "Pendaftaran Ditolak",
      badge: "Ditolak",
      badgeClass:
        "bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-900/40",
      description:
        "Mohon maaf, pendaftaran keanggotaan Anda telah ditolak oleh Koperasi. Akun Anda berada dalam status baca-saja (read-only).",
      icon: XCircle,
    };
  }

  if (isRevisionRequired.value) {
    return {
      tone: "warning" as const,
      title: "Perlu Revisi Dokumen / Data",
      badge: "Perlu Revisi",
      badgeClass:
        "bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-900/40",
      description:
        "Pengurus Koperasi meminta perbaikan atau kelengkapan data pendaftaran Anda. Silakan hubungi admin koperasi untuk klarifikasi.",
      icon: AlertCircle,
    };
  }

  if (isUnderReview.value) {
    return {
      tone: "warning" as const,
      title: "Menunggu Approval Pengurus",
      badge: "Menunggu Pengurus",
      badgeClass:
        "bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-900/40",
      description:
        "Data pendaftaran Anda sudah berhasil diverifikasi oleh Admin Koperasi dan sedang menunggu persetujuan (approval) akhir dari Pengurus Koperasi.",
      icon: Clock,
    };
  }

  // WAITING_VERIFICATION or default
  return {
    tone: "default" as const,
    title: "Menunggu Verifikasi Admin Koperasi",
    badge: "Menunggu Admin",
    badgeClass:
      "bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900/40",
    description:
      "Data pendaftaran Anda telah tercatat dalam sistem. Saat ini berkas Anda sedang dalam antrean verifikasi oleh Admin Koperasi.",
    icon: Sparkles,
  };
});

const steps = computed(() => [
  {
    title: "Pendaftaran Tercatat",
    desc: "Data tersimpan di sistem",
    status: "completed",
  },
  {
    title: "Verifikasi Admin",
    desc: "Pemeriksaan berkas & identitas",
    status: isWaitingVerification.value
      ? "current"
      : "completed",
  },
  {
    title: "Approval Pengurus",
    desc: "Persetujuan final maker-checker",
    status: isRejected.value
      ? "rejected"
      : isRevisionRequired.value
        ? "revision"
        : isUnderReview.value
          ? "current"
          : "pending",
  },
  {
    title: "Anggota Aktif",
    desc: "Akses penuh fitur Kojayaku",
    status: "pending",
  },
]);
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Status Pendaftaran', href: '/member/onboarding' },
    ]"
  >
    <Head title="Status Pendaftaran Anggota" />

    <PageContainer>
      <div class="space-y-6">
        <!-- Header -->
        <header class="flex items-center gap-3 sm:gap-5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white shadow-lg shadow-emerald-600/20 sm:h-16 sm:w-16"
          >
            <Sparkles class="h-6 w-6 sm:h-8 sm:w-8" />
          </div>
          <div>
            <h1
              class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl"
            >
              Status Pendaftaran Anggota
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              {{ member.organization?.name || "Koperasi" }} ·
              {{ member.name || member.user?.name || "Calon Anggota" }}
            </p>
          </div>
        </header>

        <!-- Status Banner Alert -->
        <Alert
          :variant="statusMeta.tone === 'destructive' ? 'destructive' : 'default'"
          class="rounded-2xl border p-4 sm:p-5"
        >
          <component :is="statusMeta.icon" class="h-5 w-5" />
          <AlertTitle class="text-base font-semibold">
            {{ statusMeta.title }}
          </AlertTitle>
          <AlertDescription class="mt-1 text-sm leading-relaxed">
            {{ statusMeta.description }}
          </AlertDescription>
        </Alert>

        <!-- Rejection / Revision Notes if available -->
        <div
          v-if="(isRejected || isRevisionRequired) && validation_notes"
          class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
        >
          <div class="flex items-start gap-3">
            <FileText class="mt-0.5 h-5 w-5 shrink-0 text-zinc-500" />
            <div>
              <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                Catatan Pengurus / Admin:
              </h3>
              <p class="mt-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
                {{ validation_notes }}
              </p>
            </div>
          </div>
        </div>

        <!-- Read-Only Notice for Rejected -->
        <div
          v-if="isRejected"
          class="rounded-2xl border border-rose-200/80 bg-rose-50/50 p-4 text-xs leading-relaxed text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300 sm:p-5"
        >
          Pendaftaran ini telah berstatus ditolak dan tidak dapat diajukan kembali secara mandiri.
          Akses transaksi dan fitur finansial tidak tersedia. Hubungi pengurus koperasi untuk keterangan lebih lanjut.
        </div>

        <!-- Lifecycle Progression Stepper -->
        <section
          class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 sm:p-6"
        >
          <h2
            class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
          >
            Alur Verifikasi Keanggotaan
          </h2>

          <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="(step, idx) in steps"
              :key="step.title"
              class="relative rounded-2xl border p-4 transition-all"
              :class="[
                step.status === 'completed'
                  ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/30 dark:bg-emerald-950/10'
                  : step.status === 'current'
                    ? 'border-amber-300 bg-amber-50/50 dark:border-amber-800/40 dark:bg-amber-950/20 ring-2 ring-amber-400/30'
                    : step.status === 'rejected'
                      ? 'border-rose-300 bg-rose-50/50 dark:border-rose-800/40 dark:bg-rose-950/20'
                      : step.status === 'revision'
                        ? 'border-amber-300 bg-amber-50/50 dark:border-amber-800/40 dark:bg-amber-950/20'
                        : 'border-zinc-200 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-800/20 text-zinc-400',
              ]"
            >
              <div class="flex items-center gap-3">
                <span
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                  :class="[
                    step.status === 'completed'
                      ? 'bg-emerald-600 text-white'
                      : step.status === 'current'
                        ? 'bg-amber-500 text-white animate-pulse'
                        : step.status === 'rejected'
                          ? 'bg-rose-600 text-white'
                          : step.status === 'revision'
                            ? 'bg-amber-600 text-white'
                            : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300',
                  ]"
                >
                  <CheckCircle2
                    v-if="step.status === 'completed'"
                    class="h-4 w-4"
                  />
                  <Clock
                    v-else-if="step.status === 'current'"
                    class="h-4 w-4"
                  />
                  <XCircle
                    v-else-if="step.status === 'rejected'"
                    class="h-4 w-4"
                  />
                  <AlertCircle
                    v-else-if="step.status === 'revision'"
                    class="h-4 w-4"
                  />
                  <span v-else>{{ idx + 1 }}</span>
                </span>
                <div>
                  <h3
                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                  >
                    {{ step.title }}
                  </h3>
                  <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ step.desc }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Member Profile Overview (Read-only) -->
        <section
          class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 sm:p-6"
        >
          <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
            <div class="flex items-center gap-2.5">
              <ShieldCheck class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
              <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Informasi Pendaftaran
              </h2>
            </div>
            <span
              class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
              :class="statusMeta.badgeClass"
            >
              {{ statusMeta.badge }}
            </span>
          </div>

          <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <User class="h-3.5 w-3.5" />
                Nama Terdaftar
              </dt>
              <dd class="mt-1.5 font-semibold text-zinc-900 dark:text-zinc-100">
                {{ member.name || member.user?.name || "-" }}
              </dd>
            </div>

            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <Mail class="h-3.5 w-3.5" />
                Email Akun
              </dt>
              <dd class="mt-1.5 font-semibold text-zinc-900 dark:text-zinc-100 break-all">
                {{ member.email || member.user?.email || "-" }}
              </dd>
            </div>

            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <Building class="h-3.5 w-3.5" />
                Unit Koperasi
              </dt>
              <dd class="mt-1.5 font-semibold text-zinc-900 dark:text-zinc-100">
                {{ member.organization?.name || "Koperasi Pusat" }}
              </dd>
            </div>

            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <FileText class="h-3.5 w-3.5" />
                Nomor Anggota
              </dt>
              <dd class="mt-1.5 font-semibold text-zinc-900 dark:text-zinc-100">
                {{ member.member_no || "Menunggu Aktivasi" }}
              </dd>
            </div>
          </dl>
        </section>

        <!-- Information Notice Footer -->
        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
          Setelah pendaftaran disetujui penuh oleh Pengurus Koperasi, akun Anda akan otomatis aktif dan
          seluruh layanan koperasi (simpanan, pinjaman, dan transaksi kasir) akan terbuka.
        </p>
      </div>
    </PageContainer>
  </AppLayout>
</template>
