<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Upload, FileText, Trash2, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    project: any;
    documents: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Operations', href: '#' },
    { title: 'Projects', href: projectsIndex().url },
    { title: props.project.name, href: `/projects/${props.project.id}` },
    { title: 'Documents', href: '#' },
];

const documentTypeColors: Record<string, string> = {
    SIKA: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    PERMIT: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    DRAWING: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    OTHER: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
};

const documentStatusColors: Record<string, string> = {
    VALID: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    EXPIRED: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const showUploadDialog = ref(false);

const documentForm = useForm({
    name: '',
    type: 'OTHER',
    file: null as File | null,
    expiry_date: '',
});

const submitDocument = () => {
    const formData = new FormData();
    formData.append('name', documentForm.name);
    formData.append('type', documentForm.type);
    if (documentForm.file) {
        formData.append('file', documentForm.file);
    }
    if (documentForm.expiry_date) {
        formData.append('expiry_date', documentForm.expiry_date);
    }

    documentForm.post(`/projects/${props.project.id}/documents`, {
        forceFormData: true,
        onSuccess: () => {
            showUploadDialog.value = false;
            documentForm.reset();
        },
    });
};

const deleteDocument = (documentId: string) => {
    if (confirm('Are you sure you want to delete this document?')) {
        router.delete(`/projects/${props.project.id}/documents/${documentId}`, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};
</script>

<template>
    <Head>
        <title>Documents - {{ props.project.name }}</title>
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" as-child>
                        <Link :href="`/projects/${props.project.id}`">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ props.project.name }}</h1>
                        <p class="text-sm text-zinc-500">{{ props.project.project_code }}</p>
                    </div>
                </div>
                <Dialog v-model:open="showUploadDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Upload Document
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Upload Document</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitDocument" class="space-y-4 mt-2">
                            <div class="grid gap-2">
                                <Label for="doc_name">Document Name</Label>
                                <Input id="doc_name" v-model="documentForm.name" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="doc_type">Document Type</Label>
                                <select id="doc_type" v-model="documentForm.type" class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-2 bg-white dark:bg-zinc-900">
                                    <option value="SIKA">SIKA</option>
                                    <option value="PERMIT">Permit</option>
                                    <option value="DRAWING">Drawing</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="doc_file">File (PDF/JPG, max 5MB)</Label>
                                <Input id="doc_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @input="(e: any) => documentForm.file = e.target.files[0]" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="doc_expiry">Expiry Date (Optional)</Label>
                                <Input id="doc_expiry" type="date" v-model="documentForm.expiry_date" />
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" @click="showUploadDialog = false">Cancel</Button>
                                <Button type="submit" :disabled="documentForm.processing">Upload</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Documents Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Project Documents</h3>
                    <p class="text-sm text-zinc-500">
                        Manage safety documents, permits, and drawings
                    </p>
                </div>

                <div v-if="props.documents && props.documents.length > 0" class="space-y-3">
                    <div v-for="doc in props.documents" :key="doc.id" class="flex items-center gap-3 p-4 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ doc.name }}</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="documentTypeColors[doc.type]">
                                    {{ doc.type }}
                                </span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="documentStatusColors[doc.status]">
                                    {{ doc.status }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-zinc-500">
                                <span v-if="doc.expiry_date">Expires: {{ doc.expiry_date }}</span>
                                <span>• {{ new Date(doc.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                        <button 
                            @click="deleteDocument(doc.id)"
                            class="text-zinc-400 hover:text-red-500 transition-colors"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <FileText class="h-12 w-12 text-zinc-400 mx-auto mb-4" />
                    <p class="text-sm text-zinc-500">No documents uploaded yet.</p>
                    <Button @click="showUploadDialog = true" variant="outline" class="mt-4">
                        <Plus class="h-4 w-4 mr-2" />
                        Upload First Document
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
