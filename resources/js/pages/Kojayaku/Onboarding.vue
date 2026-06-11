<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  AlertCircle,
  CheckCircle2,
  CircleDot,
  CreditCard,
  FileSignature,
  IdCard,
  MapPin,
  ShieldCheck,
  Sparkles,
  User
  
} from "lucide-vue-next";
import type {LucideIcon} from "lucide-vue-next";
import { computed, ref } from "vue";
import OnboardingChecklist from "@/components/Kojayaku/OnboardingChecklist.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";

type Member = {
  id: number;
  member_no: string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  identity_number?: string | null;
  jenis_kelamin?: string | null;
  kategori?: string | null;
  tanggal_lahir?: string | null;
  tempat_lahir?: string | null;
  pekerjaan?: string | null;
  npwp?: string | null;
  no_rekening?: string | null;
  nama_bank?: string | null;
  nama_pemilik_rekening?: string | null;
  validation_status?: string | null;
  organization?: { name: string } | null;
  user?: { name?: string; email?: string };
};

type OnboardingStatus = {
  completed_steps: number;
  total_steps: number;
  progress_percent: number;
  is_complete: boolean;
  is_dismissed: boolean;
  steps: Array<{
    key: string;
    label: string;
    description: string;
    href: string;
    completed: boolean;
  }>;
};

type Options = {
  jenisKelamin: Array<{ value: string; label: string }>;
  perusahaan: Array<{ value: string; label: string }>;
  bank: Array<{ value: string; label: string }>;
};

const props = defineProps<{
  member: Member;
  onboarding: OnboardingStatus;
  submitted: boolean;
  review_state:
    | "draft"
    | "review"
    | "revision"
    | "rejected"
    | "approved"
    | "pending";
  validation_status: string;
  options: Options;
}>();

const stepOrder = [
  { key: "personal", label: "Data Pribadi", icon: User },
  { key: "contact", label: "Kontak & Alamat", icon: MapPin },
  { key: "identity", label: "Identitas", icon: IdCard },
  { key: "membership", label: "Keanggotaan", icon: FileSignature },
  { key: "bank", label: "Rekening", icon: CreditCard },
  { key: "review", label: "Review & Submit", icon: ShieldCheck },
] as const;

const activeStep = ref<number>(0);

const isLocked = computed<boolean>(() => {
  return ["approved", "rejected"].includes(props.review_state);
});

const isApproved = computed<boolean>(() => props.review_state === "approved");

const isAdmissionWaiting = computed<boolean>(() => {
  return props.validation_status === "PENDING" && !props.submitted;
});

const admissionWaitingTitle = computed<string>(() =>
  props.validation_status === "PENDING_VALIDATION"
    ? "Menunggu approval Pengurus Koperasi"
    : "Menunggu penerimaan Admin Koperasi",
);

const admissionWaitingDescription = computed<string>(() =>
  props.validation_status === "PENDING_VALIDATION"
    ? "Data Anda sudah diverifikasi Admin Koperasi. Akses Kojayaku akan dibuka setelah Pengurus Koperasi memberikan approval final."
    : "Akun Google Anda sudah berhasil dibuat sebagai calon anggota. Untuk menjaga validasi data koperasi, akses Kojayaku baru akan dibuka setelah Admin Koperasi menerima pendaftaran ini.",
);

const admissionWaitingStatus = computed<string>(() =>
  props.validation_status === "PENDING_VALIDATION"
    ? "Menunggu Pengurus"
    : "Menunggu Admin",
);

const form = useForm<{
  name: string;
  email: string;
  phone: string;
  address: string;
  identity_number: string;
  npwp: string;
  jenis_kelamin: string;
  kategori: string;
  tanggal_lahir: string;
  tempat_lahir: string;
  pekerjaan: string;
  no_rekening: string;
  nama_bank: string;
  nama_pemilik_rekening: string;
}>({
  name: props.member.name ?? props.member.user?.name ?? "",
  email: props.member.email ?? props.member.user?.email ?? "",
  phone: props.member.phone ?? "",
  address: props.member.address ?? "",
  identity_number: props.member.identity_number ?? "",
  npwp: props.member.npwp ?? "",
  jenis_kelamin: props.member.jenis_kelamin ?? "L",
  kategori: props.member.kategori ?? "IP",
  tanggal_lahir: props.member.tanggal_lahir ?? "",
  tempat_lahir: props.member.tempat_lahir ?? "",
  pekerjaan: props.member.pekerjaan ?? "",
  no_rekening: props.member.no_rekening ?? "",
  nama_bank: props.member.nama_bank ?? "",
  nama_pemilik_rekening: props.member.nama_pemilik_rekening ?? "",
});

const setStep = (index: number): void => {
  if (isLocked.value) return;
  activeStep.value = Math.max(0, Math.min(stepOrder.length - 1, index));
};

const next = (): void => {
  setStep(activeStep.value + 1);
};

const back = (): void => {
  setStep(activeStep.value - 1);
};

const submit = (): void => {
  form.post("/member/onboarding", { preserveScroll: true });
};

const reviewItems = computed(() => [
  { label: "Nama lengkap", value: form.name },
  { label: "Email", value: form.email },
  { label: "Nomor HP", value: form.phone },
  { label: "Alamat", value: form.address },
  { label: "Nomor Identitas", value: form.identity_number },
  { label: "NPWP", value: form.npwp || "-" },
  { label: "Jenis Kelamin", value: form.jenis_kelamin },
  { label: "Perusahaan", value: form.kategori },
  { label: "Tanggal Lahir", value: form.tanggal_lahir || "-" },
  { label: "Tempat Lahir", value: form.tempat_lahir || "-" },
  { label: "Jabatan", value: form.pekerjaan || "-" },
  { label: "Bank", value: form.nama_bank || "-" },
  { label: "Nama Pemilik Rekening", value: form.nama_pemilik_rekening || "-" },
  { label: "Nomor Rekening", value: form.no_rekening || "-" },
]);

const formError = computed<string>(() => String(form.errors.form ?? ""));

const reviewStateMeta = computed<{
  tone: "warning" | "success" | "destructive" | "secondary";
  title: string;
  description: string;
  icon: LucideIcon;
}>(() => {
  switch (props.review_state) {
    case "review":
      return {
        tone: "warning",
        title: "Menunggu Approval Pengurus",
        description:
          "Data Anda sudah diverifikasi Admin Koperasi dan sedang menunggu approval final Pengurus Koperasi.",
        icon: Sparkles,
      };
    case "revision":
      return {
        tone: "warning",
        title: "Perlu Revisi",
        description:
          "Pengurus meminta perbaikan data. Silakan perbarui dan kirim ulang.",
        icon: AlertCircle,
      };
    case "rejected":
      return {
        tone: "destructive",
        title: "Ditolak",
        description:
          "Pengurus menolak pendaftaran ini. Hubungi admin untuk informasi lebih lanjut.",
        icon: AlertCircle,
      };
    case "approved":
      return {
        tone: "success",
        title: "Disetujui",
        description:
          "Selamat! Anda sudah menjadi anggota aktif. Anda bisa mengakses fitur anggota.",
        icon: CheckCircle2,
      };
    case "pending":
    case "draft":
    default:
      return {
        tone: "secondary",
        title: isAdmissionWaiting.value
          ? "Penerimaan Anggota Baru"
          : "Onboarding Diterima",
        description: isAdmissionWaiting.value
          ? "Pendaftaran Anda sudah masuk ke sistem. Mohon konfirmasi ke Admin Koperasi agar akun dapat diterima sebagai anggota."
          : "Lengkapi semua langkah dan submit data Anda untuk validasi pengurus.",
        icon: ShieldCheck,
      };
  }
});

const stepClass = (key: string): string =>
  isLocked.value ? "text-zinc-500" : "text-zinc-900";
</script>

<template>
  <Head title="Onboarding Kojayaku" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Onboarding', href: '/member/onboarding' },
    ]"
  >
    <PageContainer>
      <div class="space-y-2 rounded-lg border p-5">
        <p class="text-sm text-muted-foreground">
          {{ member.member_no }} · {{ member.organization?.name || "Koperasi" }}
        </p>
        <h1 class="text-2xl font-semibold">Onboarding {{ member.name }}</h1>
      </div>

      <Alert
        :variant="
          reviewStateMeta.tone === 'destructive' ? 'destructive' : 'default'
        "
      >
        <component :is="reviewStateMeta.icon" class="h-4 w-4" />
        <AlertTitle>{{ reviewStateMeta.title }}</AlertTitle>
        <AlertDescription>{{ reviewStateMeta.description }}</AlertDescription>
      </Alert>

      <section
        v-if="isAdmissionWaiting"
        class="grid gap-4 rounded-lg border border-emerald-200 bg-emerald-50/70 p-6 text-emerald-950 shadow-sm"
        data-test="member-admission-waiting"
      >
        <div class="flex items-start gap-4">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-emerald-700 shadow-sm"
          >
            <ShieldCheck class="h-5 w-5" />
          </div>
          <div class="space-y-2">
            <h2 class="text-lg font-semibold">
              {{ admissionWaitingTitle }}
            </h2>
            <p class="max-w-3xl text-sm leading-6 text-emerald-900/85">
              {{ admissionWaitingDescription }}
            </p>
          </div>
        </div>

        <div
          class="grid gap-3 rounded-md border border-emerald-200 bg-white/75 p-4 text-sm sm:grid-cols-3"
        >
          <div>
            <p
              class="text-xs font-medium uppercase tracking-wide text-emerald-700"
            >
              Nama
            </p>
            <p class="mt-1 font-semibold">
              {{ member.name || member.user?.name || "-" }}
            </p>
          </div>
          <div>
            <p
              class="text-xs font-medium uppercase tracking-wide text-emerald-700"
            >
              Email Google
            </p>
            <p class="mt-1 font-semibold">
              {{ member.email || member.user?.email || "-" }}
            </p>
          </div>
          <div>
            <p
              class="text-xs font-medium uppercase tracking-wide text-emerald-700"
            >
              Status
            </p>
            <p class="mt-1 font-semibold">{{ admissionWaitingStatus }}</p>
          </div>
        </div>

        <p class="text-xs leading-5 text-emerald-800">
          Jika Anda sudah menghubungi Admin Koperasi, silakan tunggu sampai
          status diterima. Setelah diterima, menu anggota seperti simpanan,
          pinjaman, poin, dan transaksi akan otomatis terbuka.
        </p>
      </section>

      <OnboardingChecklist
        v-if="!isAdmissionWaiting"
        :onboarding="onboarding"
      />

      <section v-if="!isAdmissionWaiting" class="rounded-lg border p-5">
        <ol
          class="flex flex-wrap items-center gap-2 text-sm"
          data-test="onboarding-step-nav"
        >
          <li
            v-for="(step, index) in stepOrder"
            :key="step.key"
            :data-active="activeStep === index"
            :data-completed="index < activeStep"
          >
            <button
              type="button"
              class="flex items-center gap-2 rounded-full border px-3 py-1.5 transition disabled:cursor-not-allowed disabled:opacity-50"
              :class="[
                activeStep === index
                  ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                  : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
              ]"
              :disabled="isLocked"
              @click="setStep(index)"
            >
              <span
                class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold"
                :class="
                  activeStep === index
                    ? 'bg-emerald-600 text-white'
                    : 'bg-zinc-100 text-zinc-500'
                "
              >
                {{ index + 1 }}
              </span>
              <component :is="step.icon" class="h-3.5 w-3.5" />
              <span :class="stepClass(step.key)">{{ step.label }}</span>
            </button>
          </li>
        </ol>
      </section>

      <form
        v-if="!isAdmissionWaiting"
        class="rounded-lg border p-5"
        data-test="onboarding-form"
        @submit.prevent="submit"
      >
        <div v-show="activeStep === 0" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="onb-name">Nama Lengkap</Label>
              <Input
                id="onb-name"
                v-model="form.name"
                :disabled="isLocked"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="onb-email">Email</Label>
              <Input
                id="onb-email"
                v-model="form.email"
                type="email"
                :disabled="isLocked"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="onb-tanggal-lahir">Tanggal Lahir</Label>
              <Input
                id="onb-tanggal-lahir"
                v-model="form.tanggal_lahir"
                type="date"
                :disabled="isLocked"
              />
            </div>
            <div class="space-y-2">
              <Label for="onb-tempat-lahir">Tempat Lahir</Label>
              <Input
                id="onb-tempat-lahir"
                v-model="form.tempat_lahir"
                :disabled="isLocked"
              />
            </div>
          </div>
        </div>

        <div v-show="activeStep === 1" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="onb-phone">Nomor HP</Label>
              <Input
                id="onb-phone"
                v-model="form.phone"
                :disabled="isLocked"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="onb-pekerjaan">Jabatan</Label>
              <Input
                id="onb-pekerjaan"
                v-model="form.pekerjaan"
                :disabled="isLocked"
              />
            </div>
            <div class="space-y-2 md:col-span-2">
              <Label for="onb-address">Alamat Domisili</Label>
              <textarea
                id="onb-address"
                v-model="form.address"
                class="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-60"
                :disabled="isLocked"
                required
              />
            </div>
          </div>
        </div>

        <div v-show="activeStep === 2" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="onb-identity">Nomor Identitas (NIK/KTP)</Label>
              <Input
                id="onb-identity"
                v-model="form.identity_number"
                :disabled="isLocked"
                required
              />
              <p class="text-xs text-muted-foreground">
                Data identitas dipakai untuk validasi pengurus.
              </p>
            </div>
            <div class="space-y-2">
              <Label for="onb-npwp">NPWP</Label>
              <Input
                id="onb-npwp"
                v-model="form.npwp"
                placeholder="contoh: 12.345.678.9-012.000"
                :disabled="isLocked"
              />
              <p class="text-xs text-muted-foreground">
                Opsional. Digunakan untuk kebutuhan administrasi perpajakan koperasi.
              </p>
            </div>
          </div>
        </div>

        <div v-show="activeStep === 3" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Jenis Kelamin</Label>
              <Select v-model="form.jenis_kelamin" :disabled="isLocked">
                <SelectTrigger>
                  <SelectValue placeholder="Pilih jenis" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in options.jenisKelamin"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Perusahaan</Label>
              <Select v-model="form.kategori" :disabled="isLocked">
                <SelectTrigger>
                  <SelectValue placeholder="Pilih perusahaan" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in options.perusahaan"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </div>

        <div v-show="activeStep === 4" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-2">
              <Label>Nama Bank</Label>
              <Select v-model="form.nama_bank" :disabled="isLocked">
                <SelectTrigger>
                  <SelectValue placeholder="Pilih bank" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in options.bank"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2 md:col-span-2">
              <Label for="onb-pemilik">Nama Pemilik Rekening</Label>
              <Input
                id="onb-pemilik"
                v-model="form.nama_pemilik_rekening"
                :disabled="isLocked"
              />
            </div>
            <div class="space-y-2 md:col-span-3">
              <Label for="onb-norek">Nomor Rekening</Label>
              <Input
                id="onb-norek"
                v-model="form.no_rekening"
                :disabled="isLocked"
              />
            </div>
          </div>
        </div>

        <div v-show="activeStep === 5" class="space-y-4">
          <div class="rounded-lg border bg-zinc-50 p-4 text-sm">
            <p class="font-semibold">Review Data Onboarding</p>
            <p class="text-muted-foreground">
              Pastikan data yang Anda kirim sudah benar. Setelah submit,
              pengurus akan memvalidasi.
            </p>
          </div>
          <dl class="grid gap-3 sm:grid-cols-2">
            <div
              v-for="item in reviewItems"
              :key="item.label"
              class="rounded-lg border p-3"
            >
              <dt class="text-xs text-muted-foreground">{{ item.label }}</dt>
              <dd class="mt-1 text-sm font-medium">{{ item.value || "-" }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="formError" class="mt-4">
          <Alert variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Onboarding belum lengkap</AlertTitle>
            <AlertDescription>{{ formError }}</AlertDescription>
          </Alert>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
          <Button
            v-if="activeStep > 0"
            type="button"
            variant="outline"
            :disabled="isLocked"
            @click="back"
          >
            Kembali
          </Button>
          <span v-else />
          <div class="flex flex-wrap items-center gap-2">
            <Button
              v-if="activeStep < stepOrder.length - 1"
              type="button"
              :disabled="isLocked"
              data-test="onboarding-next"
              @click="next"
            >
              Lanjut
            </Button>
            <Button
              v-else
              type="submit"
              :disabled="form.processing || isLocked"
              data-test="onboarding-submit"
            >
              <CircleDot class="h-4 w-4" />
              {{ isApproved ? "Sudah Disetujui" : "Submit Onboarding" }}
            </Button>
          </div>
        </div>
      </form>

      <div
        v-if="!submitted && !isLocked"
        class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
      >
        Onboarding akan dikirim ke pengurus setelah Anda menekan tombol
        <strong>Submit Onboarding</strong>. Sebelum submit, data dianggap masih
        draf.
      </div>

      <div
        v-if="isApproved"
        class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
      >
        <p class="font-semibold">Selamat datang di koperasi.</p>
        <p>
          Anda bisa mengakses
          <Link href="/member/savings" class="font-semibold underline"
            >simpanan</Link
          >,
          <Link href="/member/loans" class="font-semibold underline"
            >pinjaman</Link
          >, dan
          <Link href="/member/rewards" class="font-semibold underline"
            >reward</Link
          >.
        </p>
      </div>
    </PageContainer>
  </AppLayout>
</template>
