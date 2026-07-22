<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, Clock, XCircle } from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

defineProps<{
    openShift: {
        id: number;
        shift_no: string;
        shift_date: string;
        opened_at: string;
        opening_cash: number | string;
    } | null;
    shifts: { data: Array<Record<string, unknown>> };
    locations: Array<{ id: number; name: string }>;
}>();

const openForm = useForm({
    opening_cash: 0,
    pos_inventory_location_id: null as number | null,
    notes: "",
});

function openShiftFn(): void {
    openForm.post("/cooperative/pos/shifts/open", { preserveScroll: true });
}

const closingShiftId = ref<number | null>(null);
const closeForm = useForm({
    closing_cash: 0,
    notes: "",
});

function startClose(id: number, expected: number): void {
    closingShiftId.value = id;
    closeForm.closing_cash = Number(expected);
    closeForm.notes = "";
}

function submitClose(): void {
    if (!closingShiftId.value) return;
    closeForm.post(`/cooperative/pos/shifts/${closingShiftId.value}/close`);
}
</script>

<template>
    <Head title="Shift Kasir" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Shift', href: '/cooperative/pos/shifts' },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center gap-4">
                    <Link href="/cooperative/pos" prefetch>
                        <Button variant="ghost" size="icon" class="rounded-full">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Shift Kasir</h1>
                        <p class="text-sm text-zinc-500">Buka dan tutup shift kasir dengan penghitungan uang.</p>
                    </div>
                </header>

                <div v-if="openShift" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Shift Aktif</p>
                            <p class="mt-1 text-xl font-extrabold text-emerald-800">{{ openShift.shift_no }}</p>
                            <p class="text-xs text-emerald-700">
                                Dibuka: {{ formatDate(openShift.opened_at) }} · Kas awal: {{ formatCurrency(openShift.opening_cash) }}
                            </p>
                        </div>
                        <Button class="rounded-xl" @click="startClose(openShift.id, Number(openShift.opening_cash))">
                            <XCircle class="mr-2 h-4 w-4" />
                            Tutup Shift
                        </Button>
                    </div>
                </div>

                <form v-else @submit.prevent="openShiftFn" class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold">Buka Shift Baru</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div>
                            <Label>Kas Awal</Label>
                            <Input v-model="openForm.opening_cash" type="number" min="0" class="mt-1 rounded-xl" />
                        </div>
                        <div>
                            <Label>Lokasi (opsional)</Label>
                            <select v-model="openForm.pos_inventory_location_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                                <option :value="null">-</option>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                        </div>
                        <div>
                            <Label>Catatan</Label>
                            <Input v-model="openForm.notes" class="mt-1 rounded-xl" />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <Button type="submit" :disabled="openForm.processing" class="rounded-xl">
                            <Clock class="mr-2 h-4 w-4" />
                            Buka Shift
                        </Button>
                    </div>
                </form>

                <div v-if="closingShiftId" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-bold text-zinc-900">Tutup Shift</h3>
                        <p class="mt-1 text-sm text-zinc-500">Hitung uang fisik di laci dan masukkan nilai closing.</p>
                        <div class="mt-4 space-y-3">
                            <div>
                                <Label>Kas Dihitung</Label>
                                <Input v-model="closeForm.closing_cash" type="number" min="0" class="mt-1 rounded-xl" />
                            </div>
                            <div>
                                <Label>Catatan</Label>
                                <Input v-model="closeForm.notes" class="mt-1 rounded-xl" />
                            </div>
                        </div>
                        <div class="mt-5 flex justify-end gap-2">
                            <Button variant="outline" class="rounded-xl" @click="closingShiftId = null">Batal</Button>
                            <Button class="rounded-xl" :disabled="closeForm.processing" @click="submitClose">
                                <CheckCircle2 class="mr-2 h-4 w-4" /> Konfirmasi
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-100 bg-white shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">No. Shift</th>
                                <th class="px-4 py-3">Kasir</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3 text-right">Kas Awal</th>
                                <th class="px-4 py-3 text-right">Penjualan</th>
                                <th class="px-4 py-3 text-right">Selisih</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in (shifts.data as Array<any>)" :key="s.id" class="border-b border-zinc-50">
                                <td class="px-4 py-2 font-mono text-xs">{{ s.shift_no }}</td>
                                <td class="px-4 py-2">{{ s.cashier?.name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ s.shift_date }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(s.opening_cash) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(s.total_sales) }}</td>
                                <td class="px-4 py-2 text-right" :class="Number(s.cash_difference ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ formatCurrency(s.cash_difference ?? 0) }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span :class="s.status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-700'" class="rounded-md px-2 py-1 text-[10px] font-extrabold uppercase">
                                        {{ s.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
