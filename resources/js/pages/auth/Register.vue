<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import {
  Check,
  Eye,
  EyeOff,
  Headphones,
  LockKeyhole,
  Mail,
  MapPin,
  Phone,
  ShieldCheck,
  UserRound,
  Users,
} from "lucide-vue-next";
import { ref } from "vue";
import InputError from "@/components/InputError.vue";
import TextLink from "@/components/TextLink.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Spinner } from "@/components/ui/spinner";

import { store } from "@/routes/register";

const passwordVisible = ref(false);
const confirmVisible = ref(false);

const form = useForm({
  name: "",
  email: "",
  phone: "",
  identity_number: "",
  address: "",
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(store.url, {
    onFinish: () => {
      form.reset("password", "password_confirmation");
    },
  });
};
</script>

<template>
  <Head title="Daftar" />

  <main
    class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-[#f7fbf6] text-slate-950 px-4 py-6 sm:px-6"
  >
    <div
      class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(16,128,49,0.08),transparent_34%,rgba(16,128,49,0.07)_100%)]"
    />
    <div
      class="pointer-events-none absolute top-8 left-8 grid grid-cols-5 gap-3 opacity-20"
      aria-hidden="true"
    >
      <span
        v-for="dot in 25"
        :key="dot"
        class="size-1.5 rounded-full bg-green-500"
      />
    </div>
    <div
      class="pointer-events-none absolute right-8 bottom-8 grid grid-cols-5 gap-3 opacity-18"
      aria-hidden="true"
    >
      <span
        v-for="dot in 25"
        :key="dot"
        class="size-1.5 rounded-full bg-green-500"
      />
    </div>

    <div
      class="relative w-full max-w-lg rounded-xl border border-white/80 bg-white/95 px-5 py-6 shadow-[0_24px_60px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8 sm:py-8"
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
        <Users class="size-5" />
        <div class="h-px flex-1 bg-green-200" />
      </div>

      <div class="text-center">
        <h2 class="text-2xl font-black text-slate-950 sm:text-3xl">
          Daftar Anggota
        </h2>
        <p class="mt-2 text-sm text-slate-600 sm:text-base">
          Buat akun untuk mengakses portal anggota
        </p>
      </div>

      <form
        @submit.prevent="submit"
        class="mt-6 flex flex-col gap-4"
      >
        <div class="grid gap-2">
          <Label for="name" class="text-sm font-bold text-slate-950">
            Nama Lengkap
          </Label>
          <div class="relative">
            <UserRound
              class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
            />
            <Input
              id="name"
              v-model="form.name"
              type="text"
              required
              autofocus
              autocomplete="name"
              placeholder="Masukkan nama lengkap Anda"
              class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
          </div>
          <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
          <Label for="email" class="text-sm font-bold text-slate-950">
            Email
          </Label>
          <div class="relative">
            <Mail
              class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
            />
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              placeholder="Masukkan email Anda"
              class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
          </div>
          <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
          <Label for="phone" class="text-sm font-bold text-slate-950">
            Nomor Telepon
          </Label>
          <div class="relative">
            <Phone
              class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
            />
            <Input
              id="phone"
              v-model="form.phone"
              type="text"
              autocomplete="tel"
              placeholder="Masukkan nomor telepon"
              class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
          </div>
          <InputError :message="form.errors.phone" />
        </div>

        <div class="grid gap-2">
          <Label for="identity_number" class="text-sm font-bold text-slate-950">
            NIK / Nomor Identitas
          </Label>
          <div class="relative">
            <ShieldCheck
              class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
            />
            <Input
              id="identity_number"
              v-model="form.identity_number"
              type="text"
              autocomplete="off"
              placeholder="Masukkan NIK Anda"
              class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
          </div>
          <InputError :message="form.errors.identity_number" />
        </div>

        <div class="grid gap-2">
          <Label for="address" class="text-sm font-bold text-slate-950">
            Alamat
          </Label>
          <div class="relative">
            <MapPin
              class="pointer-events-none absolute top-[14px] left-4 size-4.5 text-slate-500"
            />
            <Input
              id="address"
              v-model="form.address"
              type="text"
              autocomplete="street-address"
              placeholder="Masukkan alamat Anda"
              class="h-13 rounded-lg border-slate-300 bg-white pr-4 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
          </div>
          <InputError :message="form.errors.address" />
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
              v-model="form.password"
              :type="passwordVisible ? 'text' : 'password'"
              required
              autocomplete="new-password"
              placeholder="Minimal 8 karakter"
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
              @click="passwordVisible = !passwordVisible"
            >
              <EyeOff v-if="passwordVisible" class="size-4.5" />
              <Eye v-else class="size-4.5" />
            </button>
          </div>
          <InputError :message="form.errors.password" />
        </div>

        <div class="grid gap-2">
          <Label
            for="password_confirmation"
            class="text-sm font-bold text-slate-950"
          >
            Konfirmasi Kata Sandi
          </Label>
          <div class="relative">
            <LockKeyhole
              class="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-slate-500"
            />
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="confirmVisible ? 'text' : 'password'"
              required
              autocomplete="new-password"
              placeholder="Ketik ulang kata sandi"
              class="h-13 rounded-lg border-slate-300 bg-white pr-12 pl-11 text-sm shadow-none focus-visible:border-[#0b8f2e] focus-visible:ring-[#0b8f2e]/20"
            />
            <button
              type="button"
              class="absolute top-1/2 right-4 -translate-y-1/2 text-slate-500 transition hover:text-[#0b8f2e] focus-visible:ring-2 focus-visible:ring-[#0b8f2e]/30 focus-visible:outline-none"
              :aria-label="
                confirmVisible
                  ? 'Sembunyikan kata sandi'
                  : 'Tampilkan kata sandi'
              "
              @click="confirmVisible = !confirmVisible"
            >
              <EyeOff v-if="confirmVisible" class="size-4.5" />
              <Eye v-else class="size-4.5" />
            </button>
          </div>
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <div class="grid gap-2.5">
          <Button
            type="submit"
            class="h-12.5 w-full rounded-lg bg-[#0b8f2e] text-base font-extrabold text-white shadow-[0_12px_24px_rgba(11,143,46,0.24)] hover:bg-[#087a28] focus-visible:ring-[#0b8f2e]/35"
            :disabled="form.processing"
          >
            <Spinner v-if="form.processing" />
            <UserRound v-else class="size-4.5" />
            Daftar
          </Button>
        </div>
      </form>

      <div
        class="mt-5 flex items-center justify-center border-t border-slate-200 pt-5"
      >
        <TextLink
          href="/login"
          class="text-sm font-bold text-[#087a28] no-underline hover:text-[#0b8f2e] hover:underline"
        >
          Sudah punya akun? Masuk di sini
        </TextLink>
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
          <Users class="size-4 text-[#0b8f2e]" />
          Portal Anggota
        </span>
        <span class="hidden text-slate-500 sm:inline">&middot;</span>
        <span class="inline-flex items-center gap-2">
          <Headphones class="size-4 text-[#0b8f2e]" />
          Dukungan 24/7
        </span>
      </div>
    </div>

    <footer
      class="relative mx-auto mt-6 flex w-full max-w-lg flex-col items-center justify-between gap-2 px-6 text-xs font-medium text-slate-600 sm:flex-row"
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
