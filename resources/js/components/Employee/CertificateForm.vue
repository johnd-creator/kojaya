<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { FileText, Calendar, Save, X } from "lucide-vue-next";
import { computed, ref } from "vue";
import { createCertificate, updateCertificate } from "@/api/certificates.ts";
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
import { Textarea } from "@/components/ui/textarea";
import type {
  CertificateType,
  CertificateFormData,
  EmployeeCertificate,
} from "@/types";

type Props = {
  employeeId: string;
  certificate?: EmployeeCertificate;
};

const props = defineProps<Props>();

const isEdit = computed(() => !!props.certificate);

const form = useForm<CertificateFormData>({
  certificate_type: "SIO_K3",
  certificate_number: "",
  issue_date: "",
  expiry_date: null,
  issuing_authority: "",
  notes: "",
});

const documentFile = ref<File | null>(null);
const documentPreview = ref<string | null>(null);
const submitting = ref(false);

const certificateTypes: { value: CertificateType; label: string }[] = [
  { value: "SIO_K3", label: "SIO K3" },
  { value: "TRAINING", label: "Training" },
  { value: "OTHER", label: "Other" },
];

const handleSubmit = async () => {
  submitting.value = true;
  try {
    const data = { ...form };
    if (documentFile.value) {
      data.document = documentFile.value;
    }

    if (isEdit.value) {
      await updateCertificate(props.employeeId, props.certificate!.id, data);
    } else {
      await createCertificate(props.employeeId, data);
    }

    router.visit(`/employees/${props.employeeId}/certificates`);
  } catch (error) {
    console.error("Failed to save certificate:", error);
  } finally {
    submitting.value = false;
  }
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      alert("File size must be less than 2MB");
      return;
    }

    // Validate file type
    const allowedTypes = [
      "application/pdf",
      "image/jpeg",
      "image/png",
      "image/jpg",
    ];
    if (!allowedTypes.includes(file.type)) {
      alert("Only PDF, JPG, and PNG files are allowed");
      return;
    }

    documentFile.value = file;

    // Create preview for images
    if (file.type.startsWith("image/")) {
      documentPreview.value = URL.createObjectURL(file);
    }
  }
};

const clearFile = () => {
  documentFile.value = null;
  documentPreview.value = null;
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          {{ isEdit ? "Edit Certificate" : "Add Certificate" }}
        </h3>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          {{
            isEdit
              ? "Update certificate information"
              : "Add new certificate for employee"
          }}
        </p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <Label for="certificate_type">Certificate Type *</Label>
          <Select v-model="form.certificate_type" required>
            <SelectTrigger>
              <SelectValue placeholder="Select type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="type in certificateTypes"
                :key="type.value"
                :value="type.value"
              >
                {{ type.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="certificate_number">Certificate Number *</Label>
          <Input
            id="certificate_number"
            v-model="form.certificate_number"
            required
            placeholder="e.g., SIO-K3-12345"
            :disabled="submitting"
          />
        </div>

        <div class="space-y-2">
          <Label for="issue_date">Issue Date *</Label>
          <Input
            id="issue_date"
            v-model="form.issue_date"
            type="date"
            required
            :disabled="submitting"
          />
        </div>

        <div class="space-y-2">
          <Label for="expiry_date">Expiry Date</Label>
          <Input
            id="expiry_date"
            v-model="form.expiry_date"
            type="date"
            :disabled="submitting"
          />
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            Leave empty if certificate doesn't expire
          </p>
        </div>

        <div class="space-y-2 md:col-span-2">
          <Label for="issuing_authority">Issuing Authority</Label>
          <Input
            id="issuing_authority"
            v-model="form.issuing_authority"
            placeholder="e.g., Ministry of Manpower"
            :disabled="submitting"
          />
        </div>

        <div class="space-y-2 md:col-span-2">
          <Label for="document">Document</Label>
          <div class="flex items-center gap-2">
            <Input
              id="document"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              class="flex-1"
              :disabled="submitting"
              @change="handleFileChange"
            />
            <Button
              v-if="documentFile"
              type="button"
              variant="ghost"
              size="icon"
              @click="clearFile"
              :disabled="submitting"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            PDF, JPG, PNG only. Max 2MB.
          </p>
          <div v-if="documentPreview" class="mt-2">
            <img
              :src="documentPreview"
              alt="Preview"
              class="h-32 w-auto rounded border border-neutral-200"
            />
          </div>
        </div>

        <div class="space-y-2 md:col-span-2">
          <Label for="notes">Notes</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            placeholder="Additional notes..."
            rows="3"
            :disabled="submitting"
          />
        </div>
      </div>

      <div
        class="flex justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-800"
      >
        <Button
          type="button"
          variant="outline"
          @click="router.visit(`/employees/${employeeId}/certificates`)"
          :disabled="submitting"
        >
          Cancel
        </Button>
        <Button type="submit" :disabled="submitting">
          <Save class="mr-2 h-4 w-4" />
          {{ submitting ? "Saving..." : isEdit ? "Update" : "Create" }}
        </Button>
      </div>
    </form>
  </div>
</template>
