<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const open = defineModel<boolean>("open", { default: false });

const props = withDefaults(
  defineProps<{
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: "default" | "danger" | "warning";
    processing?: boolean;
    confirmButtonClass?: string;
  }>(),
  {
    title: "Konfirmasi aksi",
    message: "Apakah Anda yakin ingin melanjutkan?",
    confirmLabel: "Lanjutkan",
    cancelLabel: "Batal",
    variant: "default",
    processing: false,
    confirmButtonClass: "",
  },
);

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

const cancel = (): void => {
  open.value = false;
  emit("cancel");
};

const confirm = (): void => {
  emit("confirm");
};
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>{{ message }}</DialogDescription>
      </DialogHeader>

      <DialogFooter class="gap-2 sm:justify-end">
        <Button
          type="button"
          variant="outline"
          :disabled="processing"
          @click="cancel"
        >
          {{ cancelLabel }}
        </Button>
        <Button
          type="button"
          :variant="variant === 'danger' ? 'destructive' : 'default'"
          :class="confirmButtonClass"
          :disabled="processing"
          @click="confirm"
        >
          {{ confirmLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
