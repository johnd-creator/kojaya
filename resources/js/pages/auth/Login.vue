<script setup lang="ts">
import { Form, Head, usePage } from "@inertiajs/vue3";
import {
  BarChart3,
  Calculator,
  Check,
  Eye,
  EyeOff,
  FileText,
  LockKeyhole,
  ShieldCheck,
  ShoppingCart,
  UserRound,
  Users,
} from "lucide-vue-next";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import InputError from "@/components/InputError.vue";
import TextLink from "@/components/TextLink.vue";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Spinner } from "@/components/ui/spinner";

import { store } from "@/routes/login";
import { request } from "@/routes/password";

const googleSsoRedirectUrl = "/auth/google/redirect";

defineProps<{
  status?: string;
  canResetPassword: boolean;
}>();

const passwordVisible = ref(false);
const googleLoading = ref(false);
const previousDarkState = ref<boolean | null>(null);

onMounted(() => {
  if (typeof document === "undefined") {
    return;
  }

  const root = document.documentElement;
  previousDarkState.value = root.classList.contains("dark");
  root.classList.remove("dark");
  root.style.colorScheme = "light";
});

onBeforeUnmount(() => {
  if (typeof document === "undefined") {
    return;
  }

  document.documentElement.style.colorScheme = "";
  if (previousDarkState.value === true) {
    document.documentElement.classList.add("dark");
  }
});
const page = usePage();
const googleSsoEnabled = computed<boolean>(
  () => Boolean((page.props as { googleSsoEnabled?: boolean }).googleSsoEnabled),
);
const flashErrors = computed<{ sso?: string }>(() => {
  const errors = (page.props as { errors?: Record<string, string> }).errors;
  if (errors && typeof errors === "object" && "sso" in errors) {
    return { sso: errors.sso };
  }
  return {};
});

const startGoogleLogin = (): void => {
  googleLoading.value = true;
  window.location.href = googleSsoRedirectUrl;
};

const modules = [
  { label: "Manajemen Anggota", icon: Users },
  { label: "Simpan Pinjam", icon: FileText },
  { label: "POS & Inventori", icon: ShoppingCart },
  { label: "Akuntansi", icon: Calculator },
  { label: "Laporan Real-time", icon: BarChart3 },
  { label: "Approval Workflow", icon: ShieldCheck },
];
</script>

<template>
  <Head title="Masuk" />

  <main
    class="relative flex h-svh flex-col overflow-hidden bg-[#f7fbf6] text-slate-950 [color-scheme:light]"
  >
    <div
      class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(16,128,49,0.12),transparent_34%,rgba(16,128,49,0.11)_100%)]"
    />
    <div
      class="pointer-events-none absolute top-0 right-0 hidden h-72 w-72 border border-green-200/70 opacity-80 lg:block"
    />
    <div
      class="pointer-events-none absolute top-8 left-8 grid grid-cols-5 gap-3 opacity-30"
      aria-hidden="true"
    >
      <span
        v-for="dot in 25"
        :key="dot"
        class="size-1.5 rounded-full bg-green-500"
      />
    </div>
    <div
      class="pointer-events-none absolute right-8 bottom-8 grid grid-cols-5 gap-3 opacity-25"
      aria-hidden="true"
    >
      <span
        v-for="dot in 25"
        :key="dot"
        class="size-1.5 rounded-full bg-green-500"
      />
    </div>

    <section
      class="relative mx-auto grid w-full max-w-[1560px] flex-1 grid-cols-1 lg:grid-cols-[1.08fr_0.92fr]"
    >
      <div
        class="hidden min-h-0 flex-col overflow-hidden px-6 pt-6 pb-4 sm:px-8 lg:flex lg:px-12 xl:px-16"
      >
        <div class="flex items-center gap-4">
          <img
            src="/images/logo.png"
            alt="KojayaPro"
            class="size-12 object-contain sm:size-14"
          />
          <div>
            <p class="text-xl font-extrabold text-[#063f22] xl:text-2xl">
              KojayaPro
            </p>
            <p class="text-sm font-medium text-slate-600">
              Sistem Koperasi KOJAYA
            </p>
          </div>
        </div>

        <div class="mt-8 max-w-3xl xl:mt-10">
          <h1
            class="text-2xl leading-tight font-black text-slate-950 xl:text-4xl"
          >
            Kelola Operasional KOJAYA
            <span class="block text-[#0b8f2e]">Dalam Satu Platform</span>
          </h1>
          <div class="mt-4 h-1 w-16 rounded-full bg-[#0b8f2e]" />
          <p
            class="mt-4 max-w-2xl text-base leading-7 font-semibold text-slate-700 xl:text-lg"
          >
            Pantau data anggota, iuran, transaksi toko, stok inventori, total
            simpanan, simpan pinjam, laporan keuangan, dan approval dalam satu
            sistem.
          </p>
        </div>

        <div class="mt-5 flex min-h-0 flex-1 items-center">
          <img
            src="/images/bg-login.png"
            alt="Ilustrasi modul ERP KojayaPro"
            class="mx-auto max-h-full w-full max-w-3xl object-contain drop-shadow-[0_18px_30px_rgba(16,128,49,0.16)]"
          />
        </div>

        <div
          class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6"
          aria-label="Modul KojayaPro"
        >
          <div
            v-for="module in modules"
            :key="module.label"
            class="flex min-h-24 flex-col items-center justify-center gap-2 rounded-lg border border-slate-200/80 bg-white/90 px-2 py-3 text-center shadow-[0_10px_22px_rgba(15,23,42,0.08)]"
          >
            <component
              :is="module.icon"
              class="size-6 text-[#0b8f2e]"
              stroke-width="2.4"
            />
            <span class="text-xs leading-4 font-bold text-slate-900 xl:text-sm">
              {{ module.label }}
            </span>
          </div>
        </div>
      </div>

      <div
        class="flex min-h-0 items-center justify-center bg-[radial-gradient(circle_at_50%_30%,rgba(255,255,255,0.94),rgba(221,241,224,0.76)_58%,rgba(190,226,198,0.74))] px-5 py-5 sm:px-8 lg:px-6"
      >
        <div
          class="w-full max-w-lg rounded-lg border border-white/80 bg-white/95 px-5 py-6 shadow-[0_24px_60px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8 sm:py-8"
        >
          <div class="flex items-center justify-center gap-4">
            <img src="/images/logo.png" alt="" class="size-12 object-contain" />
            <div>
              <p class="text-xl font-extrabold text-[#063f22] sm:text-2xl">
                KojayaPro
              </p>
              <p class="text-sm font-medium text-slate-600">
                Sistem Koperasi KOJAYA
              </p>
            </div>
          </div>

          <div class="my-6 flex items-center gap-3 text-[#0b8f2e]">
            <div class="h-px flex-1 bg-green-200" />
            <ShieldCheck class="size-5" />
            <div class="h-px flex-1 bg-green-200" />
          </div>

          <div class="text-center">
            <h2 class="text-2xl font-black text-slate-950 sm:text-3xl">
              Selamat Datang
            </h2>
            <p class="mt-2 text-sm text-slate-600 sm:text-base">
              Masuk untuk melanjutkan aktivitas Anda
            </p>
          </div>

          <div
            v-if="status"
            class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
          >
            {{ status }}
          </div>

          <div
            v-if="flashErrors.sso"
            class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-center text-sm font-semibold text-red-700"
          >
            {{ flashErrors.sso }}
          </div>

          <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="mt-6 flex flex-col gap-4"
          >
            <div class="grid gap-2">
              <Label for="email" class="text-sm font-bold text-slate-950">
                Email / Username
              </Label>
              <div class="relative">
                <UserRound
                  class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
                />
                <Input
                  id="email"
                  type="text"
                  name="email"
                  required
                  autofocus
                  :tabindex="1"
                  autocomplete="username"
                  placeholder="Masukkan email atau username Anda"
                  class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
                />
              </div>
              <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
              <Label for="password" class="text-sm font-bold text-slate-950">
                Kata Sandi
              </Label>
              <div class="relative">
                <LockKeyhole
                  class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
                />
                <Input
                  id="password"
                  :type="passwordVisible ? 'text' : 'password'"
                  name="password"
                  required
                  :tabindex="2"
                  autocomplete="current-password"
                  placeholder="Masukkan kata sandi Anda"
                  class="h-13 rounded-lg border-slate-300 bg-white pr-12 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
                />
                <button
                  type="button"
                  class="absolute top-1/2 right-4 -translate-y-1/2 text-slate-500 transition hover:text-[#0b8f2e] focus-visible:ring-2 focus-visible:ring-[#0b8f2e]/30 focus-visible:outline-none"
                  :aria-label="
                    passwordVisible
                      ? 'Sembunyikan kata sandi'
                      : 'Tampilkan kata sandi'
                  "
                  :tabindex="3"
                  @click="passwordVisible = !passwordVisible"
                >
                  <EyeOff v-if="passwordVisible" class="size-4.5" />
                  <Eye v-else class="size-4.5" />
                </button>
              </div>
              <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between gap-4">
              <Label
                for="remember"
                class="flex items-center gap-3 text-sm font-medium text-slate-700"
              >
                <Checkbox
                  id="remember"
                  name="remember"
                  :tabindex="4"
                  class="size-4.5 border-slate-400 data-[state=checked]:border-[#0b8f2e] data-[state=checked]:bg-[#0b8f2e]"
                />
                <span>Ingat saya</span>
              </Label>
              <TextLink
                v-if="canResetPassword"
                :href="request()"
                class="text-sm font-bold text-[#087a28] no-underline hover:text-[#0b8f2e] hover:underline"
                :tabindex="7"
              >
                Lupa kata sandi?
              </TextLink>
            </div>

            <div class="grid gap-2.5">
              <Button
                type="submit"
                class="h-12.5 w-full rounded-lg bg-[#0b8f2e] text-base font-extrabold text-white shadow-[0_12px_24px_rgba(11,143,46,0.24)] hover:bg-[#087a28] focus-visible:ring-[#0b8f2e]/35"
                :tabindex="5"
                :disabled="processing"
                data-test="login-button"
              >
                <Spinner v-if="processing" />
                <LockKeyhole v-else class="size-4.5" />
                Masuk
              </Button>
            </div>
          </Form>

          <div v-if="googleSsoEnabled" class="mt-5">
            <div class="mb-5 flex items-center gap-3 text-slate-500">
              <div class="h-px flex-1 bg-slate-200" />
              <span class="text-xs font-semibold tracking-wider uppercase"
                >atau masuk dengan</span
              >
              <div class="h-px flex-1 bg-slate-200" />
            </div>

            <Button
              type="button"
              variant="outline"
              class="h-12.5 w-full rounded-lg border-slate-300 bg-white text-sm font-extrabold text-slate-900 shadow-none hover:border-slate-400 hover:bg-slate-50 focus-visible:ring-[#0b8f2e]/25"
              :tabindex="6"
              :disabled="googleLoading"
              data-test="google-login-button"
              @click="startGoogleLogin"
            >
              <Spinner v-if="googleLoading" />
              <svg
                v-else
                class="size-4.5"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  fill="#4285F4"
                  d="M21.805 12.23c0-.79-.071-1.55-.204-2.294H12v4.34h5.486a4.69 4.69 0 0 1-2.035 3.077v2.515h3.298c1.93-1.778 3.056-4.397 3.056-7.638Z"
                />
                <path
                  fill="#34A853"
                  d="M12 22c2.756 0 5.069-.913 6.759-2.132l-3.298-2.515c-.913.612-2.08.974-3.461.974-2.659 0-4.908-1.795-5.711-4.207H2.88v2.595A10 10 0 0 0 12 22Z"
                />
                <path
                  fill="#FBBC05"
                  d="M6.289 14.12A6.013 6.013 0 0 1 5.974 12c0-.735.112-1.45.315-2.12V7.285H2.88A10 10 0 0 0 2 12c0 1.614.386 3.14.88 4.715l3.409-2.595Z"
                />
                <path
                  fill="#EA4335"
                  d="M12 5.673c1.498 0 2.843.515 3.902 1.528l2.93-2.93C17.064 2.624 14.751 1.673 12 1.673a10 10 0 0 0-9.12 5.612L6.289 9.88C7.092 7.468 9.341 5.673 12 5.673Z"
                />
              </svg>
              Masuk dengan Google
            </Button>
          </div>

          <div
            class="mt-5 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 border-t border-slate-200 pt-5 text-xs font-medium text-slate-600 sm:text-sm"
          >
            <span class="inline-flex items-center gap-2">
              <ShieldCheck class="size-4 text-[#0b8f2e]" />
              Akses aman
            </span>
            <span class="hidden text-slate-500 sm:inline">&middot;</span>
            <span class="inline-flex items-center gap-2">
              <FileText class="size-4 text-[#0b8f2e]" />
              Audit trail
            </span>
            <span class="hidden text-slate-500 sm:inline">&middot;</span>
            <span class="inline-flex items-center gap-2">
              <Users class="size-4 text-[#0b8f2e]" />
              Multi-user
            </span>
          </div>
        </div>
      </div>
    </section>

    <footer
      class="relative mx-auto flex w-full max-w-[1560px] flex-none flex-col items-center justify-between gap-2 px-6 py-3 text-xs font-medium text-slate-600 sm:flex-row sm:px-8 lg:px-12 xl:px-16"
    >
      <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
        <span class="inline-flex items-center gap-2">
          <Check class="size-4 rounded-full bg-[#0b8f2e] p-0.5 text-white" />
          Aman
        </span>
        <span>&middot;</span>
        <span>Terintegrasi</span>
        <span>&middot;</span>
        <span>Transparan</span>
        <span>&middot;</span>
        <span>Profesional</span>
      </div>
      <p>&copy; 2026 KojayaPro. All rights reserved.</p>
    </footer>
  </main>
</template>
