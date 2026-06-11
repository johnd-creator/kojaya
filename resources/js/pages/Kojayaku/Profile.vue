<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
  CheckCircle2,
  CircleDashed,
  CircleSlash,
  Mail,
  Phone,
  ShieldCheck,
  UserCircle2
  
} from "lucide-vue-next";
import type {LucideIcon} from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";

type CompletenessField = {
  key: string;
  label: string;
  description: string;
};

type CompletenessSummary = {
  progress_percent: number;
  completed_fields: number;
  total_fields: number;
  is_complete: boolean;
  missing: CompletenessField[];
  required_fields: CompletenessField[];
  login: {
    email_verified: boolean;
    google_linked: boolean;
    provider_email: string | null;
    provider_name: string | null;
    last_login_at: string | null;
  };
};

const props = defineProps<{
  user: { name: string; email: string };
  member: {
    phone?: string | null;
    address?: string | null;
    member_no: string;
    validation_status?: string | null;
    status?: string | null;
    sso_provider?: string | null;
    last_sso_login_at?: string | null;
  };
  completeness: CompletenessSummary;
  googleSsoEnabled: boolean;
}>();

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.member.phone ?? "",
  address: props.member.address ?? "",
});

const submit = (): void => {
  form.put("/member/profile");
};

const goToOnboarding = (): void => {
  router.visit("/member/onboarding");
};

const startGoogleLogin = (): void => {
  window.location.href = "/auth/google/link";
};

const validationLabel = computed<string>(() => {
  switch (props.member.validation_status) {
    case "PENDING":
      return "Menunggu Validasi";
    case "PENDING_VALIDATION":
      return "Sedang Direview";
    case "REVISION":
      return "Perlu Revisi";
    case "REJECTED":
      return "Ditolak";
    case "ACTIVE":
      return "Disetujui";
    default:
      return props.member.validation_status ?? "-";
  }
});

const validationVariant = computed<
  "success" | "warning" | "secondary" | "destructive"
>(() => {
  switch (props.member.validation_status) {
    case "ACTIVE":
      return "success";
    case "REJECTED":
      return "destructive";
    case "REVISION":
      return "secondary";
    case "PENDING":
    case "PENDING_VALIDATION":
      return "warning";
    default:
      return "secondary";
  }
});

const progressColor = computed<string>(() => {
  if (props.completeness.progress_percent >= 100) return "bg-green-500";
  if (props.completeness.progress_percent >= 50) return "bg-amber-500";
  return "bg-red-500";
});

const fieldIcon = (completed: boolean): LucideIcon =>
  completed ? CheckCircle2 : CircleDashed;

const fieldClass = (completed: boolean): string =>
  completed
    ? "border-green-200 bg-green-50/60 text-green-900"
    : "border-amber-200 bg-amber-50/60 text-amber-900";

const formatLoginAt = (iso: string | null): string => {
  if (!iso) return "Belum pernah";
  try {
    return new Date(iso).toLocaleString("id-ID", {
      dateStyle: "medium",
      timeStyle: "short",
    });
  } catch {
    return iso;
  }
};
</script>

<template>
  <Head title="Profil Saya" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Profil', href: '/member/profile' },
    ]"
  >
    <PageContainer variant="form">
      <div class="flex flex-wrap items-start justify-between gap-4 mb-2">
        <div>
          <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Profil Anggota</h1>
          <p class="text-xs text-zinc-400 mt-1">
            Nomor Anggota: <span class="font-mono font-bold text-zinc-800 bg-zinc-100 px-2 py-0.5 rounded">{{ member.member_no }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <StatusBadge :status="validationLabel" :variant="validationVariant" />
          <Button
            v-if="member.validation_status !== 'ACTIVE'"
            variant="outline"
            class="rounded-xl px-4 font-bold text-xs uppercase"
            @click="goToOnboarding"
          >
            Lengkapi Onboarding
          </Button>
        </div>
      </div>

      <section
        class="space-y-5 rounded-xl border border-zinc-200/80 bg-white/95 p-6 shadow-sm shadow-zinc-950/5"
      >
        <header class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="flex items-center gap-2 text-lg font-bold text-zinc-900">
              <UserCircle2 class="h-5 w-5 text-emerald-600" />
              Kelengkapan Profile
            </h2>
            <p class="text-sm text-zinc-500">
              Lengkapi data di bawah ini agar pengurus dapat memvalidasi keanggotaan Anda.
            </p>
          </div>
          <div class="text-right">
            <p class="text-3xl font-extrabold leading-none text-zinc-900">
              {{ completeness.progress_percent }}<span class="text-lg text-zinc-400">%</span>
            </p>
            <p class="text-xs text-zinc-400 mt-0.5">
              {{ completeness.completed_fields }} / {{ completeness.total_fields }} field terisi
            </p>
          </div>
        </header>

        <div
          class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100"
          role="progressbar"
          :aria-valuenow="completeness.progress_percent"
          aria-valuemin="0"
          aria-valuemax="100"
        >
          <div
            class="h-full rounded-full transition-all duration-700"
            :class="progressColor"
            :style="{ width: completeness.progress_percent + '%' }"
          />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div
            v-for="field in completeness.required_fields"
            :key="field.key"
            class="flex items-start gap-3 rounded-xl border p-3.5 transition-all"
            :class="
              fieldClass(!completeness.missing.some((m) => m.key === field.key))
            "
          >
            <component
              :is="
                fieldIcon(
                  !completeness.missing.some((m) => m.key === field.key),
                )
              "
              class="mt-0.5 h-5 w-5 shrink-0"
            />
            <div>
              <p class="text-sm font-semibold text-zinc-800">{{ field.label }}</p>
              <p class="text-xs text-zinc-500 mt-0.5">{{ field.description }}</p>
            </div>
          </div>
        </div>
      </section>

      <section
        class="space-y-5 rounded-xl border border-zinc-200/80 bg-white/95 p-6 shadow-sm shadow-zinc-950/5"
      >
        <header class="border-b border-zinc-100 pb-4">
          <h2 class="flex items-center gap-2 text-lg font-bold text-zinc-900">
            <ShieldCheck class="h-5 w-5 text-emerald-600" />
            Akun Login
          </h2>
          <p class="text-sm text-zinc-500 mt-1">
            Kelola metode login dan keamanan akun Anda.
          </p>
        </header>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3.5">
            <Mail class="mt-0.5 h-5 w-5 text-zinc-500 shrink-0" />
            <div>
              <p class="text-sm font-semibold text-zinc-800">Email</p>
              <p class="text-xs text-zinc-500">{{ user.email }}</p>
              <p
                class="mt-1 text-xs font-semibold"
                :class="
                  completeness.login.email_verified
                    ? 'text-green-600'
                    : 'text-amber-600'
                "
              >
                {{
                  completeness.login.email_verified
                    ? "Terverifikasi"
                    : "Belum diverifikasi"
                }}
              </p>
            </div>
          </div>

          <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50/50 p-3.5">
            <CircleSlash
              v-if="!completeness.login.google_linked"
              class="mt-0.5 h-5 w-5 text-amber-500 shrink-0"
            />
            <CheckCircle2 v-else class="mt-0.5 h-5 w-5 text-green-600 shrink-0" />
            <div class="flex-1">
              <p class="text-sm font-semibold text-zinc-800">Google</p>
              <p
                class="text-xs"
                :class="
                  completeness.login.google_linked
                    ? 'text-green-700'
                    : 'text-amber-700'
                "
              >
                {{
                  completeness.login.google_linked
                    ? "Terhubung dengan " +
                      (completeness.login.provider_email ?? "-")
                    : "Belum terhubung"
                }}
              </p>
              <p class="mt-1 text-[11px] text-zinc-400">
                Login terakhir:
                {{ formatLoginAt(completeness.login.last_login_at) }}
              </p>
              <Button
                v-if="googleSsoEnabled && !completeness.login.google_linked"
                type="button"
                size="sm"
                variant="outline"
                class="mt-2 rounded-lg text-xs"
                data-test="profile-link-google"
                @click="startGoogleLogin"
              >
                Hubungkan Google
              </Button>
            </div>
          </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/50 p-3.5">
          <Phone class="mt-0.5 h-5 w-5 text-blue-500 shrink-0" />
          <div>
            <p class="text-sm font-semibold text-zinc-800">Notifikasi</p>
            <p class="text-xs text-zinc-500 mt-0.5">
              Login Google digunakan untuk menerima notifikasi penting. Pastikan
              email dan nomor HP Anda valid.
            </p>
          </div>
        </div>
      </section>

      <section
        class="space-y-6 rounded-xl border border-zinc-200/80 bg-white/95 p-6 shadow-sm shadow-zinc-950/5"
      >
        <header class="border-b border-zinc-100 pb-4">
          <h2 class="text-lg font-bold text-zinc-900">Data Diri</h2>
          <p class="text-sm text-zinc-500 mt-1">
            Perbarui data anggota. Perubahan akan dikirim ke pengurus untuk divalidasi.
          </p>
        </header>
        <div class="grid gap-5 md:grid-cols-2">
          <div class="space-y-1.5">
            <Label for="member-name" class="text-xs font-semibold text-zinc-600 uppercase tracking-wide">Nama Lengkap</Label>
            <Input id="member-name" v-model="form.name" class="rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label for="member-email" class="text-xs font-semibold text-zinc-600 uppercase tracking-wide">Email</Label>
            <Input id="member-email" v-model="form.email" type="email" class="rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label for="member-phone" class="text-xs font-semibold text-zinc-600 uppercase tracking-wide">No. Telepon</Label>
            <Input id="member-phone" v-model="form.phone" class="rounded-lg" />
          </div>
          <div class="space-y-1.5 md:col-span-2">
            <Label for="member-address" class="text-xs font-semibold text-zinc-600 uppercase tracking-wide">Alamat</Label>
            <textarea
              id="member-address"
              v-model="form.address"
              class="min-h-28 w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
            />
          </div>
        </div>
        <div class="flex justify-end pt-2 border-t border-zinc-100">
          <Button
            :disabled="form.processing"
            class="rounded-xl px-6 font-semibold bg-emerald-600 hover:bg-emerald-700 text-white"
            @click="submit"
          >Simpan Perubahan</Button>
        </div>
      </section>
    </PageContainer>
  </AppLayout>
</template>
