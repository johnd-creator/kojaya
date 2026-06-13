<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";

defineProps<{
    locations: Array<{ id: number; name: string }>;
    suppliers: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    pos_supplier_id: null as number | null,
    pos_inventory_location_id: 0 as number,
    reference_no: "",
    received_at: new Date().toISOString().substring(0, 10),
    notes: "",
    items: [] as Array<{
        pos_product_id: number;
        product_name: string;
        quantity: number;
        unit_cost: number;
        batch_no: string;
        expired_at: string;
    }>,
});

const total = computed(() =>
    form.items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.unit_cost) || 0), 0),
);

function addItem(): void {
    form.items.push({
        pos_product_id: 0,
        product_name: "",
        quantity: 1,
        unit_cost: 0,
        batch_no: "",
        expired_at: "",
    });
}

function removeItem(idx: number): void {
    form.items.splice(idx, 1);
}

function submit(): void {
    form.post("/cooperative/pos/inventory/receipts", { preserveScroll: true });
}
</script>

<template>
    <Head title="Catat Penerimaan Stok" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'Penerimaan Stok', href: '/cooperative/pos/inventory/receipts' },
            { title: 'Baru', href: '/cooperative/pos/inventory/receipts/create' },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center gap-4">
                    <Link href="/cooperative/pos/inventory/receipts">
                        <Button variant="ghost" size="icon" class="rounded-full">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Catat Penerimaan Stok</h1>
                        <p class="text-sm text-zinc-500">Stok akan otomatis bertambah di lokasi tujuan.</p>
                    </div>
                </header>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm md:grid-cols-2">
                        <div>
                            <Label>Lokasi Tujuan</Label>
                            <select v-model="form.pos_inventory_location_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                                <option value="0">Pilih lokasi</option>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                            <p v-if="form.errors.pos_inventory_location_id" class="mt-1 text-xs text-red-600">{{ form.errors.pos_inventory_location_id }}</p>
                        </div>
                        <div>
                            <Label>Supplier (opsional)</Label>
                            <select v-model="form.pos_supplier_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                                <option :value="null">-</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <Label>No. Referensi</Label>
                            <Input v-model="form.reference_no" class="mt-1 rounded-xl" placeholder="PO-001" />
                        </div>
                        <div>
                            <Label>Tanggal</Label>
                            <Input v-model="form.received_at" type="date" class="mt-1 rounded-xl" />
                        </div>
                        <div class="md:col-span-2">
                            <Label>Catatan</Label>
                            <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-zinc-900">Item</h2>
                            <Button type="button" variant="outline" size="sm" class="rounded-xl" @click="addItem">Tambah Item</Button>
                        </div>
                        <div v-if="form.items.length === 0" class="rounded-xl border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500">
                            Belum ada item. Klik "Tambah Item" untuk mulai.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="grid items-end gap-2 rounded-xl border border-zinc-100 bg-zinc-50/50 p-3 md:grid-cols-12"
                            >
                                <div class="md:col-span-4">
                                    <Label class="text-xs">ID Produk</Label>
                                    <Input v-model="item.pos_product_id" type="number" min="1" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-2">
                                    <Label class="text-xs">Qty</Label>
                                    <Input v-model="item.quantity" type="number" min="1" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-2">
                                    <Label class="text-xs">Harga Beli</Label>
                                    <Input v-model="item.unit_cost" type="number" min="0" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-2">
                                    <Label class="text-xs">Batch</Label>
                                    <Input v-model="item.batch_no" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-2">
                                    <Label class="text-xs">Expired</Label>
                                    <Input v-model="item.expired_at" type="date" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-12 flex justify-end">
                                    <Button type="button" variant="ghost" size="sm" class="text-red-600" @click="removeItem(idx)">Hapus</Button>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</div>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                        <div class="text-sm text-zinc-500">Total Nilai</div>
                        <div class="text-2xl font-extrabold text-zinc-900">
                            Rp {{ total.toLocaleString("id-ID") }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link href="/cooperative/pos/inventory/receipts">
                            <Button type="button" variant="outline" class="rounded-xl">Batal</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing" class="rounded-xl">
                            {{ form.processing ? "Menyimpan..." : "Simpan" }}
                        </Button>
                    </div>
                </form>
            </div>
        </PageContainer>
    </AppLayout>
</template>
