<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/formatters';

defineProps<{
    certificates: Array<{
        id: number;
        certificate_name?: string | null;
        certificate_number?: string | null;
        status?: string | null;
        issue_date?: string | null;
        expiry_date?: string | null;
    }>;
    medicalCheckups: Array<{
        id: number;
        checkup_date?: string | null;
        next_checkup_date?: string | null;
        status?: string | null;
        notes?: string | null;
    }>;
}>();
</script>

<template>
    <Head title="ESS Compliance" />

    <AppLayout :breadcrumbs="[{ title: 'ESS', href: '/ess' }, { title: 'Compliance', href: '/ess/compliance' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Sertifikat & MCU Saya</h1>
                <p class="text-sm text-muted-foreground">Pantau masa berlaku sertifikat kerja dan riwayat medical check up pribadi.</p>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3">
                        <h2 class="font-semibold">Sertifikat</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">No</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Kedaluwarsa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="certificate in certificates" :key="certificate.id" class="border-t">
                                    <td class="px-4 py-3">{{ certificate.certificate_name || '-' }}</td>
                                    <td class="px-4 py-3">{{ certificate.certificate_number || '-' }}</td>
                                    <td class="px-4 py-3">{{ certificate.status || '-' }}</td>
                                    <td class="px-4 py-3">{{ formatDate(certificate.expiry_date) }}</td>
                                </tr>
                                <tr v-if="certificates.length === 0">
                                    <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">Belum ada data sertifikat.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3">
                        <h2 class="font-semibold">Medical Check Up</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Berikutnya</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="medicalCheckup in medicalCheckups" :key="medicalCheckup.id" class="border-t">
                                    <td class="px-4 py-3">{{ formatDate(medicalCheckup.checkup_date) }}</td>
                                    <td class="px-4 py-3">{{ formatDate(medicalCheckup.next_checkup_date) }}</td>
                                    <td class="px-4 py-3">{{ medicalCheckup.status || '-' }}</td>
                                    <td class="px-4 py-3">{{ medicalCheckup.notes || '-' }}</td>
                                </tr>
                                <tr v-if="medicalCheckups.length === 0">
                                    <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">Belum ada data MCU.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
