<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, Save, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import { index, store } from '@/actions/App/Http/Controllers/ReimbursementController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    { title: 'Finance', href: '#' },
    { title: 'Reimbursements', href: index().url },
    { title: 'Create', href: '#' },
];

const categories = [
    'TRANSPORT',
    'MEAL',
    'MEDICAL',
    'LODGING',
    'OFFICE_SUPPLIES',
    'OTHER',
];

const form = useForm({
    submission_date: new Date().toISOString().split('T')[0],
    description: '',
    items: [
        {
            category: '',
            description: '',
            amount: 0,
            receipt_date: new Date().toISOString().split('T')[0],
            receipt_file: null as File | null,
        }
    ]
});

const addItem = () => {
    form.items.push({
        category: '',
        description: '',
        amount: 0,
        receipt_date: new Date().toISOString().split('T')[0],
        receipt_file: null,
    });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            // Redirect is handled by controller
        },
    });
};

const handleFileChange = (e: Event, index: number) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.items[index].receipt_file = target.files[0];
    }
};
</script>

<template>
    <Head title="Create Reimbursement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:gap-8 md:p-6 max-w-5xl mx-auto w-full">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="index().url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <h1 class="font-semibold text-lg md:text-2xl">New Reimbursement Request</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>General Information</CardTitle>
                        <CardDescription>Basic details about your reimbursement request.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="submission_date">Submission Date</Label>
                            <Input
                                id="submission_date"
                                type="date"
                                v-model="form.submission_date"
                                required
                            />
                            <p v-if="form.errors.submission_date" class="text-sm text-destructive">{{ form.errors.submission_date }}</p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="description">Description (Optional)</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="General description of the request..."
                            />
                            <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Items</h2>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Item
                        </Button>
                    </div>

                    <p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p>

                    <Card v-for="(item, idx) in form.items" :key="idx">
                        <CardContent class="pt-6 relative">
                            <Button
                                v-if="form.items.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="absolute top-2 right-2 text-destructive hover:text-destructive"
                                @click="removeItem(idx)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <Label :for="`category-${idx}`">Category</Label>
                                    <Select v-model="item.category">
                                        <SelectTrigger :id="`category-${idx}`">
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                                                {{ cat }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors[`items.${idx}.category`]" class="text-sm text-destructive">{{ form.errors[`items.${idx}.category`] }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`amount-${idx}`">Amount</Label>
                                    <Input
                                        :id="`amount-${idx}`"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model="item.amount"
                                        required
                                    />
                                    <p v-if="form.errors[`items.${idx}.amount`]" class="text-sm text-destructive">{{ form.errors[`items.${idx}.amount`] }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`receipt_date-${idx}`">Receipt Date</Label>
                                    <Input
                                        :id="`receipt_date-${idx}`"
                                        type="date"
                                        v-model="item.receipt_date"
                                        required
                                    />
                                    <p v-if="form.errors[`items.${idx}.receipt_date`]" class="text-sm text-destructive">{{ form.errors[`items.${idx}.receipt_date`] }}</p>
                                </div>

                                <div class="space-y-2 md:col-span-2 lg:col-span-3">
                                    <Label :for="`description-${idx}`">Item Description</Label>
                                    <Input
                                        :id="`description-${idx}`"
                                        v-model="item.description"
                                        placeholder="Details about this expense..."
                                        required
                                    />
                                    <p v-if="form.errors[`items.${idx}.description`]" class="text-sm text-destructive">{{ form.errors[`items.${idx}.description`] }}</p>
                                </div>

                                <div class="space-y-2 lg:col-span-3">
                                    <Label :for="`receipt_file-${idx}`">Receipt (Optional)</Label>
                                    <Input
                                        :id="`receipt_file-${idx}`"
                                        type="file"
                                        accept="image/*,.pdf"
                                        @change="(e: Event) => handleFileChange(e, idx)"
                                    />
                                    <p v-if="form.errors[`items.${idx}.receipt_file`]" class="text-sm text-destructive">{{ form.errors[`items.${idx}.receipt_file`] }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="ghost" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Submit Request
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
