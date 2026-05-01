<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store } from '@/actions/App/Http/Controllers/PettyCashTransactionController';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    isOpen: boolean;
    accountId: string;
}>();

const emit = defineEmits(['close']);

const form = useForm({
    petty_cash_account_id: props.accountId,
    transaction_date: new Date().toISOString().split('T')[0],
    type: 'DEBIT',
    amount: 0,
    description: '',
    reference_no: '',
    proof_file: null as File | null,
});

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            form.reset();
            form.petty_cash_account_id = props.accountId; // Reset wipes this too
            form.transaction_date = new Date().toISOString().split('T')[0];
            emit('close');
        },
    });
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.proof_file = target.files[0];
    }
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>New Transaction</DialogTitle>
                <DialogDescription>
                    Record a new petty cash transaction (Debit/Credit).
                </DialogDescription>
            </DialogHeader>
            
            <form @submit.prevent="submit" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="date">Date</Label>
                        <Input id="date" type="date" v-model="form.transaction_date" />
                        <span v-if="form.errors.transaction_date" class="text-sm text-red-500">{{ form.errors.transaction_date }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="DEBIT">Debit (Masuk)</SelectItem>
                                <SelectItem value="CREDIT">Credit (Keluar)</SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</span>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="amount">Amount</Label>
                    <Input id="amount" type="number" step="0.01" v-model="form.amount" placeholder="0.00" />
                    <span v-if="form.errors.amount" class="text-sm text-red-500">{{ form.errors.amount }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="reference">Reference No (Optional)</Label>
                    <Input id="reference" v-model="form.reference_no" placeholder="e.g. INV-001" />
                    <span v-if="form.errors.reference_no" class="text-sm text-red-500">{{ form.errors.reference_no }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" placeholder="Description of the transaction..." />
                    <span v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="proof">Proof File (Optional)</Label>
                    <Input id="proof" type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.pdf" />
                    <span v-if="form.errors.proof_file" class="text-sm text-red-500">{{ form.errors.proof_file }}</span>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="$emit('close')">Cancel</Button>
                <Button type="submit" @click="submit" :disabled="form.processing">
                    Record Transaction
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
