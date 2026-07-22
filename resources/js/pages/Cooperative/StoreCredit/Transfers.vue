<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDateTime } from "@/lib/formatters";
import { index, process, proof } from "@/routes/cooperative/store-credit/transfers";

type Transfer = {
    id: number;
    amount: number;
    status: string;
    status_label: string;
    method_label: string;
    bank_reference: string | null;
    has_proof: boolean;
    created_at: string | null;
    account: { member: { full_name: string | null } | null } | null;
};

defineProps<{
    transfers: { data: Transfer[]; links: any[] };
    filters: { status?: string };
}>();

const approveForm = useForm({});
const rejectForm = useForm<{ rejection_reason: string }>({ rejection_reason: "" });

function approve(id: number): void {
    approveForm.post(process({ funding: id }).url, {
        preserveScroll: true,
        data: { decision: "approve" },
    });
}

function reject(id: number): void {
    rejectForm.post(process({ funding: id }).url, {
        preserveScroll: true,
        data: { decision: "reject", rejection_reason: rejectForm.rejection_reason },
    });
}
</script>

<template>
    <Head title="Verifikasi Setoran Transfer" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '#' },
            { title: 'Verifikasi Transfer', href: index.url() },
        ]"
    >
        <PageContainer class="max-w-5xl">
            <Link href="/cooperative/store-credit" class="inline-flex items-center text-sm text-indigo-600 hover:underline">
                <ArrowLeft class="mr-1 size-4" /> Kembali
            </Link>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Verifikasi Setoran Transfer</h1>

            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="p-3">Anggota</th>
                                <th class="p-3 text-right">Jumlah</th>
                                <th class="p-3">Bank Ref</th>
                                <th class="p-3">Waktu</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="transfer in transfers.data" :key="transfer.id" class="border-b last:border-0">
                                <td class="p-3 font-medium">{{ transfer.account?.member?.full_name ?? '-' }}</td>
                                <td class="p-3 text-right font-mono">{{ formatCurrency(transfer.amount) }}</td>
                                <td class="p-3">{{ transfer.bank_reference ?? '-' }}</td>
                                <td class="p-3">{{ formatDateTime(transfer.created_at) }}</td>
                                <td class="p-3"><Badge :variant="transfer.status === 'pending' ? 'secondary' : 'default'">{{ transfer.status_label }}</Badge></td>
                                <td class="p-3">
                                    <div v-if="transfer.status === 'pending'" class="flex flex-wrap gap-1">
                                        <Button v-if="transfer.has_proof" size="sm" variant="outline" as="a" :href="proof.url({ funding: transfer.id })" target="_blank">
                                            Lihat Bukti
                                        </Button>
                                        <Button size="sm" @click="approve(transfer.id)">Setujui</Button>
                                        <Button size="sm" variant="outline" @click="reject(transfer.id)">Tolak</Button>
                                    </div>
                                    <span v-else-if="transfer.has_proof" class="flex gap-1">
                                        <Button size="sm" variant="ghost" as="a" :href="proof.url({ funding: transfer.id })" target="_blank">
                                            Lihat Bukti
                                        </Button>
                                    </span>
                                    <span v-else class="text-zinc-400">-</span>
                                </td>
                            </tr>
                            <tr v-if="transfers.data.length === 0">
                                <td colspan="6" class="p-6 text-center text-zinc-500">Tidak ada setoran transfer menunggu verifikasi.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex items-center justify-center gap-2 p-3">
                        <Link
                            v-for="(link, i) in transfers.links"
                            :key="i"
                            :href="link?.url || '#'"
                            :class="['rounded px-3 py-1 text-sm', link?.active ? 'bg-indigo-600 text-white' : 'hover:bg-zinc-100', !link?.url && 'pointer-events-none opacity-40']"
                            v-html="link?.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </PageContainer>
    </AppLayout>
</template>
