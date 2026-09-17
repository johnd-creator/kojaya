<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import {
  AlertCircle,
  AlertTriangle,
  ArrowLeft,
  Building2,
  Calendar,
  CheckCircle2,
  Download,
  FileSpreadsheet,
  FileText,
  Info,
  RefreshCw,
  ShieldCheck,
  Upload,
  XCircle,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatNumber } from "@/lib/formatters";

interface ImportValidationErrorPayload {
  row: number | null;
  field: string;
  code: string;
  message: string;
  severity: string;
}

interface ImportRowResultPayload {
  row_number: number;
  valid: boolean;
  raw_data: Record<string, any>;
  normalized_data: {
    member_number: string | null;
    full_name: string;
    email: string;
    phone_number: string;
    identity_number: string;
    gender: string;
    company_code: string;
    employee_number: string | null;
    address: string;
    membership_type: string;
    join_date: string | null;
    notes: string | null;
  };
  resolved_employee_id: number | null;
  employee_resolution_status:
    | "NOT_PROVIDED"
    | "RESOLVED"
    | "UNRESOLVED"
    | "CONFLICT"
    | string;
  member_number_generation_required: boolean;
  manual_review_required: boolean;
  persistable: boolean;
  errors: ImportValidationErrorPayload[];
}

interface ImportValidationResultPayload {
  valid: boolean;
  header_valid: boolean;
  total_rows: number;
  valid_rows: number;
  invalid_rows: number;
  errors: ImportValidationErrorPayload[];
  rows: ImportRowResultPayload[];
}

interface OrganizationOption {
  id: string;
  code: string;
  name: string;
}

const props = withDefaults(
  defineProps<{
    is_global: boolean;
    current_organization_id: string | null;
    organizations: OrganizationOption[];
    default_import_date: string;
    canonical_headers: string[];
    preview: ImportValidationResultPayload | null;
    preview_proof?: string | null;
    file_sha256?: string | null;
    execution_enabled?: boolean;
  }>(),
  {
    preview_proof: null,
    file_sha256: null,
    execution_enabled: false,
  },
);

const flash = computed(() => (usePage().props.flash as any) ?? {});

const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const activeFilter = ref<"all" | "valid" | "invalid" | "manual_review">("all");
const isConfirmDialogOpen = ref(false);

const form = useForm({
  file: null as File | null,
  organization_id:
    props.current_organization_id ?? props.organizations[0]?.id ?? "",
  import_date: props.default_import_date,
});

const executeForm = useForm({
  file: null as File | null,
  organization_id:
    props.current_organization_id ?? props.organizations[0]?.id ?? "",
  import_date: props.default_import_date,
  preview_proof: props.preview_proof ?? "",
  confirm_import: true,
});

const openConfirmDialog = (): void => {
  isConfirmDialogOpen.value = true;
};

const closeConfirmDialog = (): void => {
  isConfirmDialogOpen.value = false;
};

const submitExecute = (): void => {
  const fileToSubmit = selectedFile.value || form.file;
  if (!fileToSubmit) {
    return;
  }

  executeForm.file = fileToSubmit;
  executeForm.organization_id = form.organization_id;
  executeForm.import_date = form.import_date;
  executeForm.preview_proof = props.preview_proof ?? "";
  executeForm.confirm_import = true;

  executeForm.post("/cooperative/members/import/execute", {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isConfirmDialogOpen.value = false;
    },
  });
};

const onFileChange = (e: Event): void => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    selectedFile.value = file;
    form.file = file;
  }
};

const handleDrop = (e: DragEvent): void => {
  e.preventDefault();
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
    const file = e.dataTransfer.files[0];
    selectedFile.value = file;
    form.file = file;
  }
};

const handleDragOver = (e: DragEvent): void => {
  e.preventDefault();
};

const submitPreview = (): void => {
  form.post("/cooperative/members/import/preview", {
    preserveScroll: true,
    forceFormData: true,
  });
};

const resetFile = (): void => {
  selectedFile.value = null;
  form.file = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
  router.get("/cooperative/members/import", {}, { preserveState: false });
};

const manualReviewCount = computed(() => {
  if (!props.preview?.rows) return 0;
  return props.preview.rows.filter((r) => r.manual_review_required).length;
});

const isReadyForImport = computed(() => {
  if (!props.preview) return false;
  return (
    props.preview.header_valid &&
    props.preview.total_rows > 0 &&
    props.preview.invalid_rows === 0 &&
    props.preview.rows.every((r) => r.persistable && !r.manual_review_required)
  );
});

const filteredRows = computed(() => {
  if (!props.preview?.rows) return [];
  switch (activeFilter.value) {
    case "valid":
      return props.preview.rows.filter(
        (r) => r.valid && r.persistable && !r.manual_review_required,
      );
    case "invalid":
      return props.preview.rows.filter((r) => !r.valid);
    case "manual_review":
      return props.preview.rows.filter((r) => r.manual_review_required);
    default:
      return props.preview.rows;
  }
});

const currentOrgName = computed(() => {
  if (!props.current_organization_id) {
    return props.organizations[0]?.name ?? "Semua Organisasi";
  }
  const found = props.organizations.find(
    (o) => o.id === props.current_organization_id,
  );
  return found
    ? `${found.code} — ${found.name}`
    : props.current_organization_id;
});
</script>

<template>
  <Head title="Import Anggota — Pratinjau (Dry-Run)" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: '/cooperative/members' },
      { title: 'Import Anggota', href: '/cooperative/members/import' },
    ]"
  >
    <PageContainer>
      <div class="space-y-6">
        <!-- SUCCESS BANNER AFTER EXECUTION -->
        <div
          v-if="flash?.import_result"
          class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-5 text-emerald-950 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
          >
            <div class="flex items-start gap-3">
              <CheckCircle2
                class="mt-0.5 size-6 shrink-0 text-emerald-600 dark:text-emerald-400"
              />
              <div>
                <h3
                  class="text-base font-bold text-emerald-900 dark:text-emerald-100"
                >
                  Import Anggota ke DEV Berhasil Dipersistensikan
                </h3>
                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                  Sebanyak
                  <strong>{{
                    formatNumber(flash.import_result.imported_count)
                  }}</strong>
                  anggota baru telah dibuat dengan status
                  <strong>PENDING</strong>.
                </p>
                <div
                  class="mt-3 flex flex-wrap gap-4 text-xs text-emerald-700 dark:text-emerald-400"
                >
                  <span
                    >ID Impor:
                    <code>{{ flash.import_result.import_id }}</code></span
                  >
                  <span
                    >Nomor Di-generate:
                    {{
                      flash.import_result.generated_member_number_count
                    }}</span
                  >
                  <span
                    >Nomor Disediakan:
                    {{ flash.import_result.supplied_member_number_count }}</span
                  >
                </div>
              </div>
            </div>
            <Link
              href="/cooperative/members?status=PENDING"
              class="inline-flex shrink-0 items-center justify-center gap-1 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800"
            >
              Buka Daftar Anggota (PENDING)
            </Link>
          </div>
        </div>

        <!-- HEADER SECTION -->
        <section
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <div class="flex items-center gap-2">
              <Link
                href="/cooperative/members"
                class="inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
              >
                <ArrowLeft class="size-4" />
                Daftar Anggota
              </Link>
            </div>
            <h1
              class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-3xl"
            >
              Import Anggota
            </h1>
            <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
              Unggah file CSV anggota untuk divalidasi dan ditinjau sebelum
              proses impor. Tahap ini tidak menyimpan data anggota ke database.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <a
              href="/cooperative/members/import/template"
              class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
              <Download class="size-4" />
              Unduh Template CSV
            </a>
          </div>
        </section>

        <!-- SAFETY NOTICE -->
        <div
          class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 text-sky-900 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-200"
        >
          <Info class="mt-0.5 size-5 shrink-0 text-sky-600 dark:text-sky-400" />
          <div class="text-sm">
            <p class="font-semibold">Simulasi Impor (Dry-Run Preview)</p>
            <p class="mt-0.5 text-sky-800 dark:text-sky-300">
              Pengujian ini mengevaluasi kepatuhan 12 header kanonikal,
              integritas data, deteksi duplikasi batch, benturan basis data,
              serta resolusi referensi pegawai.
              <strong
                >Tidak ada penulisan data anggota baru maupun perubahan akun
                pengguna pada tahap ini.</strong
              >
            </p>
          </div>
        </div>

        <!-- UPLOAD CARD -->
        <Card
          class="border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <CardHeader>
            <CardTitle class="text-lg font-semibold"
              >Parameter & Berkas Impor</CardTitle
            >
            <CardDescription>
              Tentukan organisasi sasaran, tanggal impor, dan berkas CSV sesuai
              spesifikasi kanonikal.
            </CardDescription>
          </CardHeader>

          <CardContent>
            <form class="space-y-6" @submit.prevent="submitPreview">
              <div class="grid gap-6 sm:grid-cols-2">
                <!-- ORGANIZATION TARGET -->
                <div class="space-y-2">
                  <Label
                    for="organization_id"
                    class="flex items-center gap-1.5 font-medium"
                  >
                    <Building2 class="size-4 text-zinc-500" />
                    Organisasi Sasaran
                    <span class="text-rose-500">*</span>
                  </Label>

                  <div v-if="is_global">
                    <select
                      id="organization_id"
                      v-model="form.organization_id"
                      class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                      required
                    >
                      <option value="" disabled>Pilih Organisasi...</option>
                      <option
                        v-for="org in organizations"
                        :key="org.id"
                        :value="org.id"
                      >
                        {{ org.code }} — {{ org.name }}
                      </option>
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">
                      Operator global wajib menentukan unit organisasi sasaran
                      secara eksplisit.
                    </p>
                  </div>

                  <div v-else>
                    <div
                      class="flex h-10 items-center rounded-md border border-zinc-200 bg-zinc-50 px-3 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200"
                    >
                      {{ currentOrgName }}
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">
                      Terkunci ke unit organisasi Anda. Operator unit tidak
                      dapat mengimpor ke unit lain.
                    </p>
                  </div>
                  <InputError :message="form.errors.organization_id" />
                </div>

                <!-- IMPORT DATE -->
                <div class="space-y-2">
                  <Label
                    for="import_date"
                    class="flex items-center gap-1.5 font-medium"
                  >
                    <Calendar class="size-4 text-zinc-500" />
                    Tanggal Impor Efektif
                    <span class="text-rose-500">*</span>
                  </Label>
                  <Input
                    id="import_date"
                    v-model="form.import_date"
                    type="date"
                    required
                    class="bg-white dark:bg-zinc-950"
                  />
                  <p class="mt-1 text-xs text-zinc-500">
                    Digunakan sebagai default jika tanggal bergabung (<code
                      class="text-xs"
                      >join_date</code
                    >) pada baris CSV kosong.
                  </p>
                  <InputError :message="form.errors.import_date" />
                </div>
              </div>

              <!-- FILE DROPZONE -->
              <div class="space-y-2">
                <Label class="font-medium">
                  Berkas CSV Anggota
                  <span class="text-rose-500">*</span>
                </Label>

                <div
                  class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-300 p-8 text-center transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600"
                  @drop="handleDrop"
                  @dragover="handleDragOver"
                >
                  <FileSpreadsheet
                    class="size-10 text-emerald-600 dark:text-emerald-400"
                  />
                  <div class="mt-3">
                    <label
                      for="file-upload"
                      class="cursor-pointer font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                      Pilih berkas CSV
                      <input
                        id="file-upload"
                        ref="fileInput"
                        type="file"
                        accept=".csv,text/csv"
                        class="sr-only"
                        @change="onFileChange"
                      />
                    </label>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                      atau seret dan lepas ke sini</span
                    >
                  </div>
                  <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Format CSV kanonikal 12 kolom (maksimal 10 MB). Berkas XLSX
                    tidak didukung.
                  </p>

                  <div
                    v-if="selectedFile"
                    class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                  >
                    <FileText class="size-4" />
                    <span
                      >{{ selectedFile.name }} ({{
                        (selectedFile.size / 1024).toFixed(1)
                      }}
                      KB)</span
                    >
                  </div>
                </div>
                <InputError :message="form.errors.file" />
              </div>

              <!-- ACTIONS -->
              <div
                class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800"
              >
                <div class="text-xs text-zinc-500">
                  Pastikan susunan header persis 12 kolom kanonikal sebelum
                  memproses.
                </div>

                <div class="flex items-center gap-2">
                  <Button
                    v-if="selectedFile || preview"
                    type="button"
                    variant="outline"
                    @click="resetFile"
                  >
                    <RefreshCw class="mr-2 size-4" />
                    Pilih Berkas Lain
                  </Button>

                  <Button
                    type="submit"
                    :disabled="form.processing || !form.file"
                    class="bg-emerald-700 text-white hover:bg-emerald-800"
                  >
                    <Upload class="mr-2 size-4" />
                    {{
                      form.processing
                        ? "Memproses Validasi..."
                        : "Uji Validasi (Pratinjau)"
                    }}
                  </Button>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- CANONICAL TEMPLATE GUIDANCE -->
        <details
          class="group rounded-2xl border border-zinc-200/80 bg-white/70 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/60"
        >
          <summary
            class="flex cursor-pointer items-center justify-between font-medium text-zinc-800 dark:text-zinc-200"
          >
            <span class="flex items-center gap-2">
              <ShieldCheck class="size-4 text-emerald-600" />
              Panduan Format Header Kanonikal 12 Kolom
            </span>
            <span
              class="text-xs text-zinc-500 group-open:rotate-180 transition-transform"
              >▼</span
            >
          </summary>
          <div
            class="mt-3 space-y-2 border-t border-zinc-100 pt-3 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-400"
          >
            <p>
              Berkas CSV <strong>wajib menyajikan tepat 12 kolom</strong> dalam
              urutan yang persis sama:
            </p>
            <div
              class="overflow-x-auto rounded-lg bg-zinc-900 p-3 text-zinc-200"
            >
              <code>{{ canonical_headers.join(",") }}</code>
            </div>
            <ul class="grid gap-1 pl-4 list-disc sm:grid-cols-2">
              <li>
                <code>member_number</code>: Nomor anggota (opsional, jika kosong
                akan di-generate saat impor).
              </li>
              <li>
                <code>full_name</code>: Nama lengkap (wajib, maksimal 100
                karakter).
              </li>
              <li><code>email</code>: Email unik valid (wajib).</li>
              <li>
                <code>phone_number</code>: Nomor HP Indonesia diawali
                08/628/+628 (wajib).
              </li>
              <li>
                <code>identity_number</code>: NIK tepat 16 digit angka (wajib).
              </li>
              <li><code>gender</code>: Jenis kelamin L atau P (wajib).</li>
              <li>
                <code>company_code</code>: Kode perusahaan IP, CDB, atau KOP
                (wajib).
              </li>
              <li>
                <code>employee_number</code>: NIP karyawan (opsional; wajib
                terdaftar di unit organisasi jika diisi).
              </li>
              <li>
                <code>address</code>: Alamat domisili (wajib, maksimal 1000
                karakter).
              </li>
              <li>
                <code>membership_type</code>: AB atau ALB (opsional, default
                AB).
              </li>
              <li>
                <code>join_date</code>: Tanggal bergabung YYYY-MM-DD (opsional,
                default ke tanggal impor).
              </li>
              <li>
                <code>notes</code>: Catatan tambahan verifikasi (opsional).
              </li>
            </ul>
          </div>
        </details>

        <!-- PREVIEW RESULTS SECTION -->
        <section v-if="preview" class="space-y-6">
          <!-- HEADER LEVEL FAILURE -->
          <div
            v-if="!preview.header_valid"
            class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200"
          >
            <div class="flex items-start gap-3">
              <XCircle class="mt-0.5 size-6 text-rose-600 dark:text-rose-400" />
              <div class="space-y-1">
                <h3 class="text-base font-bold">
                  Format Berkas Tidak Valid (Gagal Total)
                </h3>
                <p class="text-sm">
                  Format header CSV tidak sesuai kontrak kanonikal. Seluruh
                  baris ditolak secara fail-closed.
                </p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                  <li v-for="(err, idx) in preview.errors" :key="idx">
                    {{ err.message }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- SUMMARY & READINESS METRICS -->
          <div v-else class="space-y-4">
            <!-- READINESS BANNER -->
            <div
              v-if="isReadyForImport"
              class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-5 text-emerald-950 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100 sm:flex-row sm:items-center"
            >
              <div class="flex items-center gap-3">
                <CheckCircle2
                  class="size-6 text-emerald-600 dark:text-emerald-400"
                />
                <div>
                  <h4 class="font-bold">Siap untuk Tahap Impor</h4>
                  <p class="text-xs text-emerald-800 dark:text-emerald-300">
                    Seluruh baris valid dan memenuhi syarat persistensi. Proses
                    penulisan data riil dilakukan pada tahap ONB-06.
                  </p>
                </div>
              </div>
              <div
                v-if="execution_enabled && preview_proof"
                class="flex items-center gap-2"
              >
                <Button
                  type="button"
                  :disabled="executeForm.processing || !selectedFile"
                  class="bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm"
                  @click="openConfirmDialog"
                >
                  <Upload class="mr-2 size-4" />
                  {{
                    executeForm.processing
                      ? "Mengimpor ke DEV..."
                      : "Import ke DEV"
                  }}
                </Button>
              </div>

              <div
                v-else-if="!execution_enabled"
                class="flex items-center gap-2"
              >
                <Badge
                  variant="outline"
                  class="border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                >
                  DEV Gate Nonaktif
                </Badge>
                <Button
                  disabled
                  variant="outline"
                  class="border-zinc-300 text-zinc-500 opacity-60"
                  title="Eksekusi impor dinonaktifkan melalui konfigurasi cooperative.member_import_execution_enabled"
                >
                  Eksekusi Dinonaktifkan
                </Button>
              </div>

              <div v-else class="flex items-center gap-2">
                <Button
                  disabled
                  variant="outline"
                  class="border-zinc-300 text-zinc-500 opacity-60"
                >
                  Bukti Pratinjau Belum Terbit
                </Button>
              </div>
            </div>

            <div
              v-else-if="preview.total_rows === 0"
              class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
            >
              <AlertTriangle
                class="size-6 text-amber-600 dark:text-amber-400"
              />
              <div>
                <h4 class="font-bold">Berkas Kosong</h4>
                <p class="text-xs text-amber-800 dark:text-amber-300">
                  Header berkas valid, namun tidak ditemukan baris data anggota
                  untuk divalidasi.
                </p>
              </div>
            </div>

            <div
              v-else
              class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-5 text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-100"
            >
              <AlertCircle class="size-6 text-rose-600 dark:text-rose-400" />
              <div>
                <h4 class="font-bold">Perlu Perbaikan Sebelum Impor</h4>
                <p class="text-xs text-rose-800 dark:text-rose-300">
                  Terdapat baris data yang memiliki kesalahan validasi atau
                  konflik referensi pegawai. Harap tinjau tabel di bawah.
                </p>
              </div>
            </div>

            <!-- KPI SUMMARY CARDS -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <Card
                class="border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
              >
                <CardHeader class="pb-2">
                  <CardDescription
                    class="text-xs font-semibold uppercase text-zinc-500"
                    >Total Baris</CardDescription
                  >
                  <CardTitle
                    class="text-2xl font-bold text-zinc-900 dark:text-zinc-100"
                  >
                    {{ formatNumber(preview.total_rows) }}
                  </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-zinc-500"
                  >Jumlah baris data dievaluasi</CardContent
                >
              </Card>

              <Card
                class="border-emerald-200/80 bg-emerald-50/30 dark:border-emerald-900/40 dark:bg-emerald-950/20"
              >
                <CardHeader class="pb-2">
                  <CardDescription
                    class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-400"
                    >Baris Valid</CardDescription
                  >
                  <CardTitle
                    class="text-2xl font-bold text-emerald-800 dark:text-emerald-300"
                  >
                    {{ formatNumber(preview.valid_rows) }}
                  </CardTitle>
                </CardHeader>
                <CardContent
                  class="text-xs text-emerald-600 dark:text-emerald-400"
                  >Memenuhi kriteria kanonikal</CardContent
                >
              </Card>

              <Card
                class="border-rose-200/80 bg-rose-50/30 dark:border-rose-900/40 dark:bg-rose-950/20"
              >
                <CardHeader class="pb-2">
                  <CardDescription
                    class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-400"
                    >Baris Tidak Valid</CardDescription
                  >
                  <CardTitle
                    class="text-2xl font-bold text-rose-800 dark:text-rose-300"
                  >
                    {{ formatNumber(preview.invalid_rows) }}
                  </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-rose-600 dark:text-rose-400"
                  >Memiliki kesalahan field / benturan</CardContent
                >
              </Card>

              <Card
                class="border-amber-200/80 bg-amber-50/30 dark:border-amber-900/40 dark:bg-amber-950/20"
              >
                <CardHeader class="pb-2">
                  <CardDescription
                    class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-400"
                    >Perlu Review Manual</CardDescription
                  >
                  <CardTitle
                    class="text-2xl font-bold text-amber-800 dark:text-amber-300"
                  >
                    {{ formatNumber(manualReviewCount) }}
                  </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-amber-600 dark:text-amber-400"
                  >NIP tidak cocok / konflik</CardContent
                >
              </Card>
            </div>

            <!-- ROW TABLE FILTER TABS -->
            <div
              class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800"
            >
              <button
                type="button"
                :class="[
                  'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                  activeFilter === 'all'
                    ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                    : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700',
                ]"
                @click="activeFilter = 'all'"
              >
                Semua Baris ({{ preview.rows.length }})
              </button>
              <button
                type="button"
                :class="[
                  'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                  activeFilter === 'valid'
                    ? 'bg-emerald-700 text-white'
                    : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300',
                ]"
                @click="activeFilter = 'valid'"
              >
                Hanya Valid ({{
                  preview.rows.filter(
                    (r) =>
                      r.valid && r.persistable && !r.manual_review_required,
                  ).length
                }})
              </button>
              <button
                type="button"
                :class="[
                  'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                  activeFilter === 'invalid'
                    ? 'bg-rose-700 text-white'
                    : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300',
                ]"
                @click="activeFilter = 'invalid'"
              >
                Hanya Error ({{ preview.rows.filter((r) => !r.valid).length }})
              </button>
              <button
                type="button"
                :class="[
                  'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                  activeFilter === 'manual_review'
                    ? 'bg-amber-700 text-white'
                    : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300',
                ]"
                @click="activeFilter = 'manual_review'"
              >
                Perlu Review ({{ manualReviewCount }})
              </button>
            </div>

            <!-- ROW DATA TABLE -->
            <Card
              class="overflow-hidden border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
              <div class="overflow-x-auto">
                <table
                  class="w-full text-left text-xs text-zinc-700 dark:text-zinc-300"
                >
                  <thead
                    class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400"
                  >
                    <tr>
                      <th class="px-3 py-3 font-semibold">#</th>
                      <th class="px-3 py-3 font-semibold">Status Baris</th>
                      <th class="px-3 py-3 font-semibold">No. Anggota</th>
                      <th class="px-3 py-3 font-semibold">Nama Lengkap</th>
                      <th class="px-3 py-3 font-semibold">Email</th>
                      <th class="px-3 py-3 font-semibold">No. Telepon</th>
                      <th class="px-3 py-3 font-semibold">NIK</th>
                      <th class="px-3 py-3 font-semibold">JK</th>
                      <th class="px-3 py-3 font-semibold">Perusahaan</th>
                      <th class="px-3 py-3 font-semibold">
                        Status Pegawai / NIP
                      </th>
                      <th class="px-3 py-3 font-semibold">Jenis</th>
                      <th class="px-3 py-3 font-semibold">Tgl Masuk</th>
                      <th class="px-3 py-3 font-semibold">
                        Masalah / Validasi
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <tr
                      v-for="row in filteredRows"
                      :key="row.row_number"
                      :class="[
                        'transition hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40',
                        !row.valid ? 'bg-rose-50/30 dark:bg-rose-950/10' : '',
                        row.manual_review_required && row.valid
                          ? 'bg-amber-50/30 dark:bg-amber-950/10'
                          : '',
                      ]"
                    >
                      <td class="px-3 py-3 font-mono font-semibold">
                        {{ row.row_number }}
                      </td>

                      <!-- ROW STATUS -->
                      <td class="px-3 py-3">
                        <Badge
                          v-if="
                            row.valid &&
                            row.persistable &&
                            !row.manual_review_required
                          "
                          variant="outline"
                          class="border-emerald-300 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                          VALID
                        </Badge>
                        <Badge
                          v-else-if="!row.valid"
                          variant="outline"
                          class="border-rose-300 bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                        >
                          INVALID
                        </Badge>
                        <Badge
                          v-else
                          variant="outline"
                          class="border-amber-300 bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                          REVIEW
                        </Badge>
                      </td>

                      <!-- MEMBER NUMBER -->
                      <td class="px-3 py-3 font-mono">
                        <span v-if="row.normalized_data.member_number">{{
                          row.normalized_data.member_number
                        }}</span>
                        <span v-else class="text-zinc-400 italic"
                          >(Otomatis)</span
                        >
                      </td>

                      <!-- FULL NAME -->
                      <td
                        class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100"
                      >
                        {{ row.normalized_data.full_name }}
                      </td>

                      <!-- EMAIL -->
                      <td class="px-3 py-3 font-mono text-[11px]">
                        {{ row.normalized_data.email }}
                      </td>

                      <!-- PHONE -->
                      <td class="px-3 py-3 font-mono text-[11px]">
                        {{ row.normalized_data.phone_number }}
                      </td>

                      <!-- NIK (REDACTED) -->
                      <td class="px-3 py-3 font-mono text-[11px] text-zinc-500">
                        {{ row.normalized_data.identity_number }}
                      </td>

                      <!-- GENDER -->
                      <td class="px-3 py-3 text-center">
                        {{ row.normalized_data.gender }}
                      </td>

                      <!-- COMPANY -->
                      <td class="px-3 py-3 font-semibold">
                        {{ row.normalized_data.company_code }}
                      </td>

                      <!-- EMPLOYEE STATUS -->
                      <td class="px-3 py-3">
                        <div class="space-y-0.5">
                          <Badge
                            v-if="row.employee_resolution_status === 'RESOLVED'"
                            variant="outline"
                            class="border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                          >
                            Terhubung
                          </Badge>
                          <Badge
                            v-else-if="
                              row.employee_resolution_status === 'NOT_PROVIDED'
                            "
                            variant="outline"
                            class="border-zinc-200 bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                          >
                            Non-Karyawan
                          </Badge>
                          <Badge
                            v-else-if="
                              row.employee_resolution_status === 'UNRESOLVED'
                            "
                            variant="outline"
                            class="border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                          >
                            Perlu Peninjauan
                          </Badge>
                          <Badge
                            v-else-if="
                              row.employee_resolution_status === 'CONFLICT'
                            "
                            variant="outline"
                            class="border-rose-300 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                          >
                            Konflik NIP
                          </Badge>
                          <p
                            v-if="row.normalized_data.employee_number"
                            class="font-mono text-[10px] text-zinc-500"
                          >
                            NIP: {{ row.normalized_data.employee_number }}
                          </p>
                        </div>
                      </td>

                      <!-- MEMBERSHIP TYPE -->
                      <td class="px-3 py-3 font-semibold">
                        {{ row.normalized_data.membership_type }}
                      </td>

                      <!-- JOIN DATE -->
                      <td class="px-3 py-3 font-mono text-[11px]">
                        {{ row.normalized_data.join_date }}
                      </td>

                      <!-- ISSUES / ERRORS -->
                      <td class="px-3 py-3">
                        <div
                          v-if="row.errors && row.errors.length > 0"
                          class="space-y-1"
                        >
                          <div
                            v-for="(err, eIdx) in row.errors"
                            :key="eIdx"
                            class="rounded bg-rose-100/80 px-2 py-0.5 text-[11px] text-rose-800 dark:bg-rose-950/60 dark:text-rose-300"
                          >
                            <span class="font-semibold">[{{ err.field }}]</span>
                            {{ err.message }}
                          </div>
                        </div>
                        <span v-else class="text-zinc-400">—</span>
                      </td>
                    </tr>

                    <tr v-if="filteredRows.length === 0">
                      <td
                        colspan="13"
                        class="py-8 text-center text-sm text-zinc-500"
                      >
                        Tidak ada baris data untuk filter ini.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </Card>
          </div>
        </section>

        <!-- CONFIRMATION MODAL -->
        <div
          v-if="isConfirmDialogOpen && preview"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
        >
          <div
            class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100"
          >
            <div class="flex items-start gap-3">
              <div
                class="rounded-xl bg-emerald-100 p-2 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
              >
                <Upload class="size-6" />
              </div>
              <div>
                <h3 class="text-lg font-bold">
                  Konfirmasi Eksekusi Impor ke DEV
                </h3>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                  Harap periksa parameter sebelum melakukan persistensi ke basis
                  data.
                </p>
              </div>
            </div>

            <div
              class="mt-5 space-y-3 rounded-xl border border-zinc-100 bg-zinc-50/80 p-4 text-xs dark:border-zinc-800 dark:bg-zinc-950/40"
            >
              <div
                class="flex justify-between border-b border-zinc-200/60 py-1 dark:border-zinc-800"
              >
                <span class="text-zinc-500">Jumlah Calon Anggota:</span>
                <span
                  class="font-semibold text-emerald-700 dark:text-emerald-400"
                  >{{ formatNumber(preview.valid_rows) }} orang</span
                >
              </div>
              <div
                class="flex justify-between border-b border-zinc-200/60 py-1 dark:border-zinc-800"
              >
                <span class="text-zinc-500">Organisasi Sasaran:</span>
                <span class="font-semibold">{{ currentOrgName }}</span>
              </div>
              <div
                class="flex justify-between border-b border-zinc-200/60 py-1 dark:border-zinc-800"
              >
                <span class="text-zinc-500">Tanggal Impor Efektif:</span>
                <span class="font-semibold">{{ form.import_date }}</span>
              </div>
              <div class="flex justify-between py-1">
                <span class="text-zinc-500">Status Awal Anggota:</span>
                <span class="font-semibold">PENDING (Validasi PENDING)</span>
              </div>
            </div>

            <div
              class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
            >
              <p class="font-semibold">Peringatan Persistensi DEV:</p>
              <p class="mt-1">
                Data akan ditulis secara transaksional (all-or-nothing) ke basis
                data DEV. Seluruh anggota akan berstatus
                <strong>PENDING</strong> dan tetap memerlukan alur verifikasi
                operasional (ONB-03).
              </p>
            </div>

            <div
              v-if="
                executeForm.errors.execution || executeForm.errors.preview_proof
              "
              class="mt-3"
            >
              <InputError
                :message="
                  executeForm.errors.execution ||
                  executeForm.errors.preview_proof
                "
              />
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <Button
                type="button"
                variant="outline"
                :disabled="executeForm.processing"
                @click="closeConfirmDialog"
              >
                Batal
              </Button>
              <Button
                type="button"
                :disabled="executeForm.processing"
                class="bg-emerald-700 text-white hover:bg-emerald-800"
                @click="submitExecute"
              >
                {{
                  executeForm.processing
                    ? "Memproses Transaksi..."
                    : "Ya, Eksekusi Impor ke DEV"
                }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
