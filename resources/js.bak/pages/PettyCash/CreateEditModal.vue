<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/PettyCashAccountController';
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

interface PettyCashAccount {
    id: string;
    organization_id: string;
    name: string;
    balance: number;
    limit: number;
    status: 'ACTIVE' | 'INACTIVE';
    description: string;
}

interface Organization {
    id: string;
    name: string;
}

const props = defineProps<{
    isOpen: boolean;
    account: PettyCashAccount | null;
    organizations: Organization[];
}>();

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.account);

const form = useForm({
    organization_id: '',
    name: '',
    limit: 0,
    status: 'ACTIVE',
    description: '',
});

// Watch for account changes to populate form
watch(() => props.account, (newAccount) => {
    if (newAccount) {
        form.organization_id = newAccount.organization_id;
        form.name = newAccount.name;
        form.limit = newAccount.limit;
        form.status = newAccount.status;
        form.description = newAccount.description;
    } else {
        form.reset();
        form.organization_id = '';
        form.name = '';
        form.limit = 0;
        form.status = 'ACTIVE';
        form.description = '';
    }
});

const submit = () => {
    if (isEditing.value && props.account) {
        form.put(update({ petty_cash: props.account.id }).url, {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(store().url, {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Edit Account' : 'Create Account' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Update petty cash account details.' : 'Add a new petty cash account.' }}
                </DialogDescription>
            </DialogHeader>
            
            <form @submit.prevent="submit" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="organization">Organization</Label>
                    <Select v-model="form.organization_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select organization" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="form.errors.organization_id" class="text-sm text-red-500">{{ form.errors.organization_id }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Account Name</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Petty Cash Finance Dept" />
                    <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="limit">Limit Amount</Label>
                    <Input id="limit" type="number" v-model="form.limit" placeholder="0.00" />
                    <span v-if="form.errors.limit" class="text-sm text-red-500">{{ form.errors.limit }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ACTIVE">Active</SelectItem>
                            <SelectItem value="INACTIVE">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" placeholder="Optional description..." />
                    <span v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</span>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="$emit('close')">Cancel</Button>
                <Button type="submit" @click="submit" :disabled="form.processing">
                    {{ isEditing ? 'Save Changes' : 'Create Account' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
