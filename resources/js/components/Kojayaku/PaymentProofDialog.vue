<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
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
import { formatCurrency } from "@/lib/formatters";
import { Loader2, Upload } from "lucide-vue-next";

const props = defineProps<{
  open: boolean;
  invoice: {
    id: number;
    amount: number;
    paid_amount: number;
    due_date: string;
  } | null;
}>();

const emit = defineEmits<{
  "update:open": [value: boolean];
}>();

const remainingAmount = () => {
  if (!props.invoice) return 0;
  return props.invoice.amount - props.invoice.paid_amount;
};

const form = useForm({
  cooperative_dues_invoice_id: null as number | null,
  amount: 0,
  payment_method: "TRANSFER",
  paid_at: new Date().toISOString().split("T")[0],
  reference_no: "",
  notes: "",
  proof: null as File | null,
});

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.proof = target.files[0];
  }
}

function submit() {
  if (!props.invoice) return;
  form.cooperative_dues_invoice_id = props.invoice.id;
  form.post("/member/payments/proof", {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      emit("update:open", false);
    },
  });
}

function handleOpenChange(value: boolean) {
  emit("update:open", value);
  if (!value) {
    form.clearErrors();
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Upload Bukti Pembayaran</DialogTitle>
        <DialogDescription>
          Upload bukti transfer pembayaran simpanan Anda. Pengurus akan
          memverifikasi dalam 1-3 hari kerja.
        </DialogDescription>
      </DialogHeader>

      <form v-if="invoice" class="space-y-4" @submit.prevent="submit">
        <div class="rounded-lg border bg-muted/50 p-4 space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Total Tagihan</span>
            <span class="font-medium">{{
              formatCurrency(invoice.amount)
            }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Sudah Dibayar</span>
            <span class="font-medium">{{
              formatCurrency(invoice.paid_amount)
            }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Sisa</span>
            <span class="font-semibold text-primary">{{
              formatCurrency(remainingAmount())
            }}</span>
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="amount">Jumlah Pembayaran</Label>
          <Input
            id="amount"
            v-model.number="form.amount"
            type="number"
            min="1"
            :max="remainingAmount()"
          />
          <p v-if="form.errors.amount" class="text-sm text-destructive">
            {{ form.errors.amount }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="payment_method">Metode Pembayaran</Label>
          <Select v-model="form.payment_method">
            <SelectTrigger id="payment_method">
              <SelectValue placeholder="Pilih metode" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="TRANSFER">Transfer Bank</SelectItem>
              <SelectItem value="QRIS">QRIS</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.payment_method" class="text-sm text-destructive">
            {{ form.errors.payment_method }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="paid_at">Tanggal Pembayaran</Label>
          <Input id="paid_at" v-model="form.paid_at" type="date" />
          <p v-if="form.errors.paid_at" class="text-sm text-destructive">
            {{ form.errors.paid_at }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="reference_no"
            >No. Referensi
            <span class="text-muted-foreground">(opsional)</span></Label
          >
          <Input
            id="reference_no"
            v-model="form.reference_no"
            placeholder="No. rekening tujuan / referensi"
          />
          <p v-if="form.errors.reference_no" class="text-sm text-destructive">
            {{ form.errors.reference_no }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="proof">Bukti Pembayaran</Label>
          <div class="flex items-center gap-3">
            <label
              for="proof"
              class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed p-4 text-sm hover:bg-muted/50 transition"
            >
              <Upload class="h-5 w-5 text-muted-foreground" />
              <span>{{
                form.proof
                  ? form.proof.name
                  : "Klik untuk upload (JPG/PNG/PDF, max 4MB)"
              }}</span>
              <input
                id="proof"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="sr-only"
                @change="handleFileChange"
              />
            </label>
          </div>
          <p v-if="form.errors.proof" class="text-sm text-destructive">
            {{ form.errors.proof }}
          </p>
        </div>

        <div class="grid gap-2">
          <Label for="notes"
            >Catatan
            <span class="text-muted-foreground">(opsional)</span></Label
          >
          <textarea
            id="notes"
            v-model="form.notes"
            class="min-h-[60px] rounded-md border bg-transparent px-3 py-2 text-sm"
            placeholder="Catatan tambahan"
          />
          <p v-if="form.errors.notes" class="text-sm text-destructive">
            {{ form.errors.notes }}
          </p>
        </div>

        <p
          v-if="form.errors.cooperative_dues_invoice_id"
          class="text-sm text-destructive"
        >
          {{ form.errors.cooperative_dues_invoice_id }}
        </p>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            @click="emit('update:open', false)"
            >Batal</Button
          >
          <Button type="submit" :disabled="form.processing">
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            Kirim Bukti Bayar
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
