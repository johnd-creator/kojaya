<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
  AlertCircle,
  BadgeCheck,
  Building2,
  CalendarDays,
  CheckCircle2,
  CircleDashed,
  Clock,
  IdCard,
  Mail,
  Pencil,
  Phone,
  RotateCcw,
  Save,
  ShieldCheck,
  UserRound,
} from "lucide-vue-next";
import type { LucideIcon } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate } from "@/lib/formatters";

type CompletenessField = { key: string; label: string; description: string };

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
    no_anggota_display?: string | null;
    nama_anggota_clean?: string | null;
    jenis_anggota_label?: string | null;
    validation_status?: string | null;
    status?: string | null;
    joined_at?: string | null;
    identity_number?: string | null;
    kategori?: string | null;
    organization?: { name: string } | null;
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

const isEditing = ref(false);

const resetForm = (): void => {
  form.reset();
  form.clearErrors();
  isEditing.value = false;
};

const startEditing = (): void => {
  form.clearErrors();
  isEditing.value = true;
};

const submitProfile = (): void => {
  form.put("/member/profile", {
    onSuccess: () => {
      isEditing.value = false;
    },
  });
};

const goToOnboarding = (): void => {
  router.visit("/member/onboarding");
};

const startGoogleLogin = (): void => {
  window.location.href = "/auth/google/link";
};

const displayName = computed<string>(
  () => props.member.nama_anggota_clean || props.user.name || "Anggota",
);

const initials = computed<string>(() => {
  const parts = displayName.value.trim().split(/\s+/);
  if (parts.length === 0) {
    return "A";
  }

  return (parts[0]?.[0] ?? "A") + (parts[1]?.[0] ?? "");
});

const memberNo = computed<string>(
  () => props.member.no_anggota_display || props.member.member_no || "-",
);

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
      return "Tervalidasi";
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

type ProgressTone = { bar: string; ring: string };

const progressTone = computed<ProgressTone>(() => {
  if (props.completeness.progress_percent >= 100) {
    return { bar: "bg-emerald-500", ring: "text-emerald-600" };
  }

  if (props.completeness.progress_percent >= 50) {
    return { bar: "bg-amber-500", ring: "text-amber-600" };
  }

  return { bar: "bg-rose-500", ring: "text-rose-600" };
});

const fieldIcon = (key: string): LucideIcon =>
  props.completeness.missing.some((missingField) => missingField.key === key)
    ? CircleDashed
    : CheckCircle2;

const fieldCompleted = (key: string): boolean =>
  !props.completeness.missing.some((missingField) => missingField.key === key);

const editableKeys: string[] = ["name", "email", "phone", "address"];

const isFieldEditable = (key: string): boolean => editableKeys.includes(key);

const formatLoginAt = (iso: string | null): string => {
  if (!iso) {
    return "Belum pernah";
  }

  try {
    return new Date(iso).toLocaleString("id-ID", {
      dateStyle: "medium",
      timeStyle: "short",
    });
  } catch {
    return iso;
  }
};

const identityItems = computed<
  Array<{ icon: LucideIcon; label: string; value: string }> | []
>(() => {
  const items: Array<{ icon: LucideIcon; label: string; value: string }> = [
    {
      icon: Building2,
      label: "Koperasi",
      value: props.member.organization?.name ?? "-",
    },
    {
      icon: IdCard,
      label: "Jenis Anggota",
      value: props.member.jenis_anggota_label ?? "-",
    },
    {
      icon: CalendarDays,
      label: "Bergabung",
      value: formatDate(props.member.joined_at ?? null),
    },
  ];

  if (props.member.kategori) {
    items.push({
      icon: BadgeCheck,
      label: "Kategori",
      value: props.member.kategori,
    });
  }

  return items;
});
</script>

<template>
  <Head title="Profil Saya" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Profil', href: '/member/profile' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-3 sm:gap-5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-lg shadow-emerald-600/20 sm:h-16 sm:w-16"
          >
            <UserRound class="h-6 w-6 sm:h-8 sm:w-8" />
          </div>
          <div class="flex-1">
            <h1
              class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl"
            >
              Profil Anggota
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Kelola data diri dan keamanan akun keanggotaan koperasi Anda.
            </p>
          </div>
        </header>

        <section
          class="overflow-hidden rounded-3xl border border-emerald-200/60 bg-gradient-to-br from-emerald-700 to-emerald-900 shadow-md shadow-emerald-800/10"
        >
          <div
            class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:gap-6 sm:p-6"
          >
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl font-extrabold uppercase tracking-wide text-white backdrop-blur-sm sm:h-20 sm:w-20 sm:text-2xl"
            >
              {{ initials }}
            </div>
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-extrabold tracking-tight text-white">
                  {{ displayName }}
                </h2>
                <StatusBadge
                  :status="validationLabel"
                  :variant="validationVariant"
                />
              </div>
              <p class="mt-1.5 text-sm text-emerald-100/80">
                Nomor Anggota:
                <span class="font-mono font-bold text-white">{{
                  memberNo
                }}</span>
              </p>
              <div
                class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4"
              >
                <div
                  v-for="item in identityItems"
                  :key="item.label"
                  class="flex items-center gap-2.5 rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur-sm"
                >
                  <component
                    :is="item.icon"
                    class="h-4 w-4 shrink-0 text-emerald-200"
                  />
                  <div class="min-w-0">
                    <p
                      class="text-[10px] font-semibold uppercase tracking-wider text-emerald-200/80"
                    >
                      {{ item.label }}
                    </p>
                    <p class="truncate text-sm font-bold text-white">
                      {{ item.value }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <Button
              v-if="member.validation_status !== 'ACTIVE'"
              variant="secondary"
              class="shrink-0 rounded-xl px-4 text-xs font-bold uppercase tracking-wider"
              @click="goToOnboarding"
            >
              <AlertCircle class="h-4 w-4" />
              Lengkapi Onboarding
            </Button>
          </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
          <section
            class="flex flex-col rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm"
          >
            <div
              class="flex items-center justify-between gap-4 border-b border-zinc-100 p-4 dark:border-zinc-800 sm:p-6"
            >
              <div class="flex min-w-0 items-center gap-4">
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400"
                >
                  <UserRound class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                  <h2
                    class="font-bold tracking-tight text-zinc-900 dark:text-white"
                  >
                    Data Diri
                  </h2>
                  <p
                    class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400"
                  >
                    {{
                      isEditing
                        ? "Perubahan akan dikirim ke pengurus untuk divalidasi."
                        : "Informasi profil anggota yang tersimpan."
                    }}
                  </p>
                </div>
              </div>
              <Button
                v-if="!isEditing"
                variant="outline"
                class="shrink-0 rounded-xl text-xs font-bold"
                @click="startEditing"
              >
                <Pencil class="h-3.5 w-3.5" />
                Edit Profil
              </Button>
              <Button
                v-else
                variant="ghost"
                class="shrink-0 rounded-xl text-xs font-semibold text-zinc-500 hover:text-zinc-700 dark:text-zinc-400"
                :disabled="form.processing"
                @click="resetForm"
              >
                Batal
              </Button>
            </div>

            <div
              v-if="!isEditing"
              class="grid flex-1 gap-3 p-4 sm:grid-cols-2 sm:gap-4 sm:p-6"
            >
              <div
                class="rounded-2xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-950/30"
              >
                <p
                  class="text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                >
                  Nama Lengkap
                </p>
                <p
                  class="mt-1.5 text-sm font-bold text-zinc-900 dark:text-white"
                >
                  {{ displayName }}
                </p>
              </div>
              <div
                class="rounded-2xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-950/30"
              >
                <p
                  class="text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                >
                  Email
                </p>
                <p
                  class="mt-1.5 break-all text-sm font-bold text-zinc-900 dark:text-white"
                >
                  {{ user.email }}
                </p>
              </div>
              <div
                class="rounded-2xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-950/30"
              >
                <p
                  class="text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                >
                  No. Telepon
                </p>
                <p
                  class="mt-1.5 text-sm font-bold text-zinc-900 dark:text-white"
                >
                  {{ member.phone || "Belum diisi" }}
                </p>
              </div>
              <div
                class="rounded-2xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-950/30"
              >
                <p
                  class="text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                >
                  Alamat
                </p>
                <p
                  class="mt-1.5 text-sm font-bold text-zinc-900 dark:text-white"
                >
                  {{ member.address || "Belum diisi" }}
                </p>
              </div>
            </div>

            <div v-else class="flex flex-1 flex-col gap-5 p-4 sm:p-6">
              <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-1.5">
                  <Label
                    for="member-name"
                    class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >Nama Lengkap</Label
                  >
                  <Input
                    id="member-name"
                    v-model="form.name"
                    class="rounded-xl dark:border-zinc-800"
                  />
                  <p
                    v-if="form.errors.name"
                    class="text-xs font-medium text-rose-600"
                  >
                    {{ form.errors.name }}
                  </p>
                </div>
                <div class="space-y-1.5">
                  <Label
                    for="member-email"
                    class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >Email</Label
                  >
                  <Input
                    id="member-email"
                    v-model="form.email"
                    type="email"
                    class="rounded-xl dark:border-zinc-800"
                  />
                  <p
                    v-if="form.errors.email"
                    class="text-xs font-medium text-rose-600"
                  >
                    {{ form.errors.email }}
                  </p>
                </div>
                <div class="space-y-1.5">
                  <Label
                    for="member-phone"
                    class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >No. Telepon</Label
                  >
                  <Input
                    id="member-phone"
                    v-model="form.phone"
                    class="rounded-xl dark:border-zinc-800"
                    placeholder="08xxxxxxxxxx"
                  />
                  <p
                    v-if="form.errors.phone"
                    class="text-xs font-medium text-rose-600"
                  >
                    {{ form.errors.phone }}
                  </p>
                </div>
                <div class="space-y-1.5">
                  <Label
                    for="member-address"
                    class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >Alamat</Label
                  >
                  <Textarea
                    id="member-address"
                    v-model="form.address"
                    :rows="3"
                    class="rounded-xl dark:border-zinc-800"
                    placeholder="Alamat domisili lengkap"
                  />
                  <p
                    v-if="form.errors.address"
                    class="text-xs font-medium text-rose-600"
                  >
                    {{ form.errors.address }}
                  </p>
                </div>
              </div>

              <div
                class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 dark:border-zinc-800 pt-5"
              >
                <p
                  v-if="form.isDirty"
                  class="flex items-center gap-1.5 text-xs font-semibold text-amber-600"
                >
                  <AlertCircle class="h-3.5 w-3.5" />
                  Ada perubahan yang belum disimpan.
                </p>
                <div v-else></div>
                <div class="flex items-center gap-2">
                  <Button
                    v-if="form.isDirty"
                    variant="ghost"
                    class="rounded-xl text-xs font-semibold text-zinc-500 hover:text-zinc-700 dark:text-zinc-400"
                    :disabled="form.processing"
                    @click="resetForm"
                  >
                    <RotateCcw class="h-3.5 w-3.5" />
                    Batal
                  </Button>
                  <Button
                    class="rounded-xl px-6 text-sm font-bold"
                    :disabled="form.processing || !form.isDirty"
                    @click="submitProfile"
                  >
                    <Save class="h-4 w-4" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Perubahan" }}
                  </Button>
                </div>
              </div>
            </div>
          </section>

          <div class="flex flex-col gap-6">
            <section
              v-if="!completeness.is_complete"
              class="rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm sm:p-6"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h2
                    class="flex items-center gap-2 text-lg font-bold tracking-tight text-zinc-900 dark:text-white"
                  >
                    <ShieldCheck class="h-5 w-5 text-emerald-600" />
                    Kelengkapan Profil
                  </h2>
                  <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Lengkapi data agar pengurus dapat memvalidasi keanggotaan
                    Anda.
                  </p>
                </div>
                <div class="text-right">
                  <p
                    class="text-3xl font-extrabold leading-none tracking-tight"
                    :class="progressTone.ring"
                  >
                    {{ completeness.progress_percent
                    }}<span class="text-lg text-zinc-300 dark:text-zinc-600"
                      >%</span
                    >
                  </p>
                  <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ completeness.completed_fields }}/{{
                      completeness.total_fields
                    }}
                    terisi
                  </p>
                </div>
              </div>

              <div
                class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                role="progressbar"
                aria-label="Kelengkapan profil"
                :aria-valuenow="completeness.progress_percent"
                aria-valuemin="0"
                aria-valuemax="100"
              >
                <div
                  class="h-full rounded-full transition-all duration-700"
                  :class="progressTone.bar"
                  :style="{ width: completeness.progress_percent + '%' }"
                />
              </div>

              <div class="mt-5 grid gap-2.5 sm:grid-cols-2">
                <div
                  v-for="field in completeness.required_fields"
                  :key="field.key"
                  class="flex items-start gap-2.5 rounded-xl border p-3 transition-colors dark:border-zinc-800/80"
                  :class="
                    fieldCompleted(field.key)
                      ? 'border-emerald-100 bg-emerald-50/50 dark:border-emerald-900/30 dark:bg-emerald-950/20'
                      : 'border-amber-100 bg-amber-50/40 dark:border-amber-900/30 dark:bg-amber-950/20'
                  "
                >
                  <component
                    :is="fieldIcon(field.key)"
                    class="mt-0.5 h-4 w-4 shrink-0"
                    :class="
                      fieldCompleted(field.key)
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-amber-500 dark:text-amber-400'
                    "
                  />
                  <div class="min-w-0 flex-1">
                    <p
                      class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                    >
                      {{ field.label }}
                    </p>
                    <p
                      class="mt-0.5 text-[11px] leading-snug text-zinc-500 dark:text-zinc-400"
                    >
                      {{ field.description }}
                    </p>
                    <p
                      v-if="
                        !isFieldEditable(field.key) &&
                        !fieldCompleted(field.key)
                      "
                      class="mt-1 text-[10px] font-semibold text-amber-700 dark:text-amber-400"
                    >
                      Diisi via onboarding
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <section
              class="overflow-hidden rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm"
            >
              <div
                class="flex items-center gap-4 border-b border-zinc-100 dark:border-zinc-800 p-4 sm:p-6"
              >
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700 shadow-sm dark:bg-blue-500/10 dark:text-blue-400"
                >
                  <ShieldCheck class="h-5 w-5" />
                </div>
                <div>
                  <h2
                    class="font-bold tracking-tight text-zinc-900 dark:text-white"
                  >
                    Akun &amp; Keamanan
                  </h2>
                  <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Kelola metode login dan keamanan akun Anda.
                  </p>
                </div>
              </div>

              <div class="flex flex-col gap-3 p-4 sm:p-6">
                <div
                  class="flex items-start gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20 p-3.5"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-800 shadow-sm"
                  >
                    <Mail class="h-4 w-4 text-zinc-500 dark:text-zinc-400" />
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <p
                        class="text-sm font-bold text-zinc-800 dark:text-zinc-200"
                      >
                        Email
                      </p>
                      <span
                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                        :class="
                          completeness.login.email_verified
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                        "
                      >
                        <CheckCircle2
                          v-if="completeness.login.email_verified"
                          class="h-3 w-3"
                        />
                        <Clock v-else class="h-3 w-3" />
                        {{
                          completeness.login.email_verified
                            ? "Terverifikasi"
                            : "Belum"
                        }}
                      </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                      {{ user.email }}
                    </p>
                  </div>
                </div>

                <div
                  class="flex items-start gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20 p-3.5"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-800 shadow-sm"
                  >
                    <component
                      :is="
                        completeness.login.google_linked
                          ? BadgeCheck
                          : CircleDashed
                      "
                      class="h-4 w-4"
                      :class="
                        completeness.login.google_linked
                          ? 'text-emerald-600 dark:text-emerald-400'
                          : 'text-amber-500 dark:text-amber-400'
                      "
                    />
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <p
                        class="text-sm font-bold text-zinc-800 dark:text-zinc-200"
                      >
                        Login Google
                      </p>
                      <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                        :class="
                          completeness.login.google_linked
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                            : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'
                        "
                      >
                        {{
                          completeness.login.google_linked
                            ? "Terhubung"
                            : "Belum"
                        }}
                      </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                      {{
                        completeness.login.google_linked
                          ? (completeness.login.provider_email ?? "-")
                          : "Hubungkan untuk login lebih cepat."
                      }}
                    </p>
                    <p
                      v-if="completeness.login.google_linked"
                      class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400"
                    >
                      Login terakhir:
                      {{ formatLoginAt(completeness.login.last_login_at) }}
                    </p>
                    <Button
                      v-if="
                        googleSsoEnabled && !completeness.login.google_linked
                      "
                      type="button"
                      size="sm"
                      variant="outline"
                      class="mt-2.5 rounded-lg text-xs"
                      data-test="profile-link-google"
                      @click="startGoogleLogin"
                    >
                      Hubungkan Google
                    </Button>
                  </div>
                </div>

                <div
                  class="flex items-start gap-3 rounded-xl border border-blue-100 dark:border-blue-900/20 bg-blue-50/40 dark:bg-blue-950/20 p-3.5"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-800 shadow-sm"
                  >
                    <Phone class="h-4 w-4 text-blue-500 dark:text-blue-400" />
                  </div>
                  <div>
                    <p
                      class="text-sm font-bold text-zinc-800 dark:text-zinc-200"
                    >
                      Notifikasi &amp; Verifikasi
                    </p>
                    <p
                      class="mt-0.5 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
                    >
                      Pastikan email dan nomor telepon Anda valid untuk menerima
                      notifikasi penting serta verifikasi keanggotaan.
                    </p>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
