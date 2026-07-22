<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import {
  FileText,
  Award,
  AlertTriangle,
  Plus,
  Upload,
  Trash2,
  Pencil,
  File,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import { fetchCertificates, deleteCertificate } from "@/api/certificates";
import CertificateBadge from "@/components/Status/CertificateBadge.vue";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import type { EmployeeCertificate, CertificateStatus } from "@/types";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

type Props = {
  employeeId: string;
  readonly?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
});

const certificates = ref<EmployeeCertificate[]>([]);
const loading = ref(false);
const deletingId = ref<string | null>(null);
const currentPage = ref(1);
const perPage = ref(10);
const total = ref(0);
const confirmDeleteId = ref<string | null>(null);
const deleteDialogOpen = computed({
  get: () => confirmDeleteId.value !== null,
  set: (open: boolean) => {
    if (!open) confirmDeleteId.value = null;
  },
});
const browserWindow = globalThis.window;

const hasCertificates = computed(() => certificates.value.length > 0);

const groupedCertificates = computed(() => {
  return {
    valid: certificates.value.filter((c) => c.status === "VALID"),
    expiring: certificates.value.filter((c) => c.status === "EXPIRING"),
    expired: certificates.value.filter(
      (c) => c.status === "EXPIRED" || c.status === "REVOKED",
    ),
  };
});

const validCount = computed(() => groupedCertificates.value.valid.length);
const expiringCount = computed(() => groupedCertificates.value.expiring.length);
const expiredCount = computed(() => groupedCertificates.value.expired.length);

const isExpiring = (certificate: EmployeeCertificate): boolean => {
  if (!certificate.expiry_date || certificate.status !== "VALID") return false;
  const expiryDate = new Date(certificate.expiry_date);
  const today = new Date();
  const daysUntilExpiry = Math.ceil(
    (expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
  );
  return daysUntilExpiry <= 60 && daysUntilExpiry > 0;
};

const formatExpiryDate = (date: string | null): string => {
  if (!date) return "No expiry";
  const d = new Date(date);
  return d.toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};

const getDaysUntilExpiry = (date: string | null): string => {
  if (!date) return "";
  const expiryDate = new Date(date);
  const today = new Date();
  const daysUntilExpiry = Math.ceil(
    (expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
  );

  if (daysUntilExpiry < 0) {
    return `Expired ${Math.abs(daysUntilExpiry)} days ago`;
  } else if (daysUntilExpiry === 0) {
    return "Expires today";
  } else if (daysUntilExpiry <= 30) {
    return `${daysUntilExpiry} days left`;
  } else {
    return `${daysUntilExpiry} days left`;
  }
};

const fetchList = async () => {
  loading.value = true;
  try {
    const response = await fetchCertificates(props.employeeId);
    certificates.value = response.data;
    total.value = response.meta.total;
  } catch (error) {
    console.error("Failed to fetch certificates:", error);
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id: string) => {
  deletingId.value = id;
  try {
    await deleteCertificate(props.employeeId, id);
    certificates.value = certificates.value.filter((c) => c.id !== id);
  } catch (error) {
    console.error("Failed to delete certificate:", error);
  } finally {
    deletingId.value = null;
  }
};

const openUpload = (certificateId: string) => {
  router.visit(
    `/employees/${props.employeeId}/certificates/${certificateId}/upload`,
  );
};

const openCreate = () => {
  router.visit(`/employees/${props.employeeId}/certificates/create`);
};

const openEdit = (certificateId: string) => {
  router.visit(
    `/employees/${props.employeeId}/certificates/${certificateId}/edit`,
  );
};

const getDocumentUrl = (path: string | null): string | null => {
  if (!path) return null;
  return `/storage/${path}`;
};

const getDocumentIcon = (path: string | null): string => {
  if (!path) return "FileText";
  const ext = path.split(".").pop()?.toLowerCase();
  if (ext === "pdf") return "File";
  return "Image";
};

onMounted(() => {
  fetchList();
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          Certificates
        </h3>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          {{ validCount }} valid, {{ expiringCount }} expiring,
          {{ expiredCount }} expired
        </p>
      </div>

      <Button v-if="!readonly" variant="outline" size="sm" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" />
        Add Certificate
      </Button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <div
        class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
      />
    </div>

    <div v-else-if="!hasCertificates" class="py-12 text-center">
      <Award
        class="mx-auto mb-4 h-16 w-16 text-neutral-300 dark:text-neutral-700"
      />
      <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
        No certificates yet
      </h3>
      <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
        Add certificates to track SIO K3 and training records
      </p>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="certificate in certificates"
        :key="certificate.id"
        class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-4 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800"
      >
        <div class="flex items-center gap-4">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900"
          >
            <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>

          <div>
            <p class="font-medium text-neutral-900 dark:text-neutral-100">
              {{ certificate.certificate_number || "No Number" }}
            </p>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
              {{ certificate.certificate_type.replace("_", " ") }}
            </p>
            <div
              class="mt-1 flex items-center gap-2 text-xs text-neutral-500 dark:text-neutral-500"
            >
              <span
                >Issued: {{ formatExpiryDate(certificate.issue_date) }}</span
              >
              <span>•</span>
              <span
                :class="[
                  certificate.status === 'EXPIRED' ||
                  certificate.status === 'REVOKED'
                    ? 'text-red-600'
                    : isExpiring(certificate)
                      ? 'text-yellow-600'
                      : 'text-green-600',
                ]"
              >
                {{ getDaysUntilExpiry(certificate.expiry_date) }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <CertificateBadge :status="certificate.status" size="sm" />

          <DropdownMenu v-if="!readonly">
            <DropdownMenuTrigger :as-child="true">
              <Button variant="ghost" size="icon" class="h-8 w-8">
                <span class="sr-only">Actions</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="lucide lucide-more-horizontal"
                >
                  <circle cx="12" cy="12" r="1" />
                  <circle cx="19" cy="12" r="1" />
                  <circle cx="5" cy="12" r="1" />
                </svg>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="openEdit(certificate.id)">
                <Pencil class="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem @click="openUpload(certificate.id)">
                <Upload class="mr-2 h-4 w-4" />
                {{ certificate.document_path ? "Replace" : "Upload" }} Document
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="certificate.document_path"
                @click="browserWindow.open(getDocumentUrl(certificate.document_path) ?? '', '_blank')"
              >
                <File class="mr-2 h-4 w-4" />
                View Document
              </DropdownMenuItem>
              <DropdownMenuItem
                class="text-red-600 focus:text-red-600"
                @click="confirmDeleteId = certificate.id"
              >
                <Trash2 class="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </div>
    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="Delete Certificate?"
      message="Are you sure you want to delete this certificate? This action cannot be undone."
      confirm-label="Delete"
      variant="danger"
      @confirm="confirmDeleteId && handleDelete(confirmDeleteId)"
      @cancel="confirmDeleteId = null"
    />
  </div>
</template>
