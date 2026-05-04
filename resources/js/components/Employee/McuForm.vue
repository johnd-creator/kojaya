<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Calendar, Save, X } from "lucide-vue-next";
import { computed, ref } from "vue";
import { createMcu, updateMcu } from "@/api/medicalCheckups.ts";
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
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import type { McuFormData, McuResult, MedicalCheckup } from "@/types";

type Props = {
  employeeId: string;
  mcu?: MedicalCheckup;
};

const props = defineProps<Props>();

const isEdit = computed(() => !!props.mcu);

const form = useForm<McuFormData>({
  checkup_date: "",
  next_checkup_date: "",
  result: "FIT",
  fit_to_work: true,
  notes: "",
  doctor_name: "",
  clinic_name: "",
});

const documentFile = ref<File | null>(null);
const documentPreview = ref<string | null>(null);
const submitting = ref(false);

const mcuResults: { value: McuResult; label: string }[] = [
  { value: "FIT", label: "Fit" },
  { value: "FIT_WITH_RESTRICTION", label: "Fit with Restriction" },
  { value: "UNFIT", label: "Unfit" },
];

const calculateNextDate = () => {
  if (form.checkup_date) {
    const checkupDate = new Date(form.checkup_date);
    const nextDate = new Date(checkupDate);
    nextDate.setFullYear(nextDate.getFullYear() + 1);
    form.next_checkup_date = nextDate.toISOString().split("T")[0];
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  try {
    const data = { ...form };
    if (documentFile.value) {
      data.document = documentFile.value;
    }

    if (isEdit.value) {
      await updateMcu(props.employeeId, props.mcu!.id, data);
    } else {
      await createMcu(props.employeeId, data);
    }

    router.visit(`/employees/${props.employeeId}/mcu`);
  } catch (error) {
    console.error("Failed to save MCU record:", error);
  } finally {
    submitting.value = false;
  }
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];

    if (file.size > 2 * 1024 * 1024) {
      alert("File size must be less than 2MB");
      return;
    }

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
          {{ isEdit ? "Edit MCU Record" : "Add Medical Check-up" }}
        </h3>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          {{
            isEdit
              ? "Update medical check-up information"
              : "Add new MCU record for employee"
          }}
        </p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <Label for="checkup_date">Check-up Date *</Label>
          <Input
            id="checkup_date"
            v-model="form.checkup_date"
            type="date"
            required
            :disabled="submitting"
            @change="calculateNextDate"
          />
        </div>

        <div class="space-y-2">
          <Label for="next_checkup_date">Next Check-up Date</Label>
          <Input
            id="next_checkup_date"
            v-model="form.next_checkup_date"
            type="date"
            :disabled="submitting"
          />
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            Typically 1 year from check-up date
          </p>
        </div>

        <div class="space-y-2">
          <Label for="result">Result *</Label>
          <Select v-model="form.result" required>
            <SelectTrigger>
              <SelectValue placeholder="Select result" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="result in mcuResults"
                :key="result.value"
                :value="result.value"
              >
                {{ result.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="fit_to_work">Fit to Work</Label>
          <div class="flex items-center gap-2">
            <Switch
              id="fit_to_work"
              :checked="form.fit_to_work"
              @update:checked="form.fit_to_work = $event"
              :disabled="submitting"
            />
            <span class="text-sm text-neutral-600 dark:text-neutral-400">
              {{ form.fit_to_work ? "Yes" : "No" }}
            </span>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="doctor_name">Doctor Name</Label>
          <Input
            id="doctor_name"
            v-model="form.doctor_name"
            placeholder="Dr. Smith"
            :disabled="submitting"
          />
        </div>

        <div class="space-y-2">
          <Label for="clinic_name">Clinic/Hospital</Label>
          <Input
            id="clinic_name"
            v-model="form.clinic_name"
            placeholder="City Hospital"
            :disabled="submitting"
          />
        </div>

        <div class="space-y-2 md:col-span-2">
          <Label for="document">Medical Result Document</Label>
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
          <Label for="notes">Notes & Recommendations</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            placeholder="Doctor's notes, recommendations, restrictions..."
            rows="4"
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
          @click="router.visit(`/employees/${employeeId}/mcu`)"
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
