<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Phone, Mail, MapPin, User, FileText, CheckCircle, AlertCircle } from 'lucide-vue-next';
import { index as clientsIndex, store as clientsStore } from '@/actions/App/Http/Controllers/ClientController';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Operations', href: '#' },
    { title: 'Clients', href: clientsIndex().url },
    { title: 'Create Client', href: '#' },
];

const props = defineProps<{
    organizations: any[];
}>();

const form = useForm({
    code: '',
    name: '',
    address: '',
    tax_id: '',
    contact_person: '',
    phone: '',
    email: '',
    client_type: 'PLN',
    organization_id: '',
});

const submit = () => {
    form.post(clientsStore().url, {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error('Form validation failed', errors);
        },
    });
};
</script>

<template>
    <Head title="Create Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-3xl mx-auto w-full">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="clientsIndex().url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Create Client</h1>
                    <p class="text-sm text-zinc-500">Add a new project client partner</p>
                </div>
            </div>

            <!-- Global Error Alert -->
            <Alert v-if="Object.keys(form.errors).length > 0" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Validation Error</AlertTitle>
                <AlertDescription>
                    Please correct the highlighted errors below before proceeding.
                </AlertDescription>
            </Alert>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Client Details</CardTitle>
                    <CardDescription>Enter the official details of the client organization.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Code & Name -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="code" class="flex items-center gap-2">
                                    <FileText class="h-3 w-3 text-zinc-500" /> Client Code <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    v-model="form.code"
                                    placeholder="e.g., PLN-001"
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.code}"
                                />
                                <p v-if="form.errors.code" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.code }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="name" class="flex items-center gap-2">
                                    <Building2 class="h-3 w-3 text-zinc-500" /> Client Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., PT PLN (Persero) UID Jawa Barat"
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.name}"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Client Type & Tax ID -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="client_type">Client Type <span class="text-red-500">*</span></Label>
                                <Select v-model="form.client_type">
                                    <SelectTrigger :class="{'border-red-500 ring-red-500': form.errors.client_type}">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="PLN">PLN (State Electricity Company)</SelectItem>
                                        <SelectItem value="PRIVATE">Private Company</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.client_type" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.client_type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="tax_id">Tax ID (NPWP)</Label>
                                <Input
                                    id="tax_id"
                                    v-model="form.tax_id"
                                    placeholder="XX.XXX.XXX.X-XXX.XXX"
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.tax_id}"
                                />
                                <p v-if="form.errors.tax_id" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.tax_id }}
                                </p>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <Label for="address" class="flex items-center gap-2">
                                <MapPin class="h-3 w-3 text-zinc-500" /> Address
                            </Label>
                            <Textarea
                                id="address"
                                v-model="form.address"
                                placeholder="Full registered address..."
                                class="min-h-[100px]"
                                :class="{'border-red-500 focus-visible:ring-red-500': form.errors.address}"
                            />
                            <p v-if="form.errors.address" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                <AlertCircle class="h-3 w-3" /> {{ form.errors.address }}
                            </p>
                        </div>

                        <div class="border-t border-zinc-100 dark:border-zinc-800 my-4"></div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                             <!-- Contact Person -->
                             <div class="space-y-2">
                                <Label for="contact_person" class="flex items-center gap-2">
                                    <User class="h-3 w-3 text-zinc-500" /> PIC Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="contact_person"
                                    v-model="form.contact_person"
                                    placeholder="Person in Charge"
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.contact_person}"
                                />
                                <p v-if="form.errors.contact_person" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.contact_person }}
                                </p>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <Label for="phone" class="flex items-center gap-2">
                                    <Phone class="h-3 w-3 text-zinc-500" /> Phone <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="+62..."
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.phone}"
                                />
                                <p v-if="form.errors.phone" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.phone }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email" class="flex items-center gap-2">
                                    <Mail class="h-3 w-3 text-zinc-500" /> Email <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    placeholder="contact@company.com"
                                    :class="{'border-red-500 focus-visible:ring-red-500': form.errors.email}"
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                    <AlertCircle class="h-3 w-3" /> {{ form.errors.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Organization -->
                        <div class="space-y-2 pt-4">
                            <Label for="organization_id">Assign to Organization (Internal)</Label>
                            <Select v-model="form.organization_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select internal organization unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="org in props.organizations" :key="org.id" :value="org.id">
                                        {{ org.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-zinc-500">
                                Optionally link this client to a specific internal business unit.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link :href="clientsIndex().url">Cancel</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                    Saving...
                                </span>
                                <span v-else>Create Client</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
