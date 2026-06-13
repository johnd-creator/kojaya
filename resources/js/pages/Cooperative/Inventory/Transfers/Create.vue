<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";

defineProps<{
    locations: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    from_location_id: 0 as number,
    to_location_id: 0 as number,
    transferred_at: new Date().toISOString().substring(0, 10),
    notes: "",
    items: [] as Array<{ pos_product_id: number; quantity: number }>,
});

function addItem(): void {
    form.items.push({ pos_product_id: 0, quantity: 1 });
}
function removeItem(idx: number): void {
    form.items.splice(idx, 1);
}
function submit(): void {
    form.post("/cooperative/pos/inventory/transfers");
}
</script>

<template>
    <Head title="Transfer Stok" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'Transfer Stok', href: '/cooperative/pos/inventory/transfers' },
            { title: 'Baru', href: '/cooperative/pos/inventory/transfers/create' },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center gap-4">
                    <Link href="/cooperative/pos/inventory/transfers">
                        <Button variant="ghost" size="icon" class="rounded-full">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Transfer Stok Baru</h1>
                    </div>
                </header>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm md:grid-cols-3">
                        <div>
                            <Label>Dari Lokasi</Label>
                            <select v-model="form.from_location_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                                <option value="0">Pilih</option>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                            <p v-if="form.errors.from_location_id" class="mt-1 text-xs text-red-600">{{ form.errors.from_location_id }}</p>
                        </div>
                        <div>
                            <Label>Ke Lokasi</Label>
                            <select v-model="form.to_location_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                                <option value="0">Pilih</option>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                            <p v-if="form.errors.to_location_id" class="mt-1 text-xs text-red-600">{{ form.errors.to_location_id }}</p>
                        </div>
                        <div>
                            <Label>Tanggal</Label>
                            <Input v-model="form.transferred_at" type="date" class="mt-1 rounded-xl" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-bold">Item</h2>
                            <Button type="button" variant="outline" size="sm" class="rounded-xl" @click="addItem">Tambah Item</Button>
                        </div>
                        <div v-if="form.items.length === 0" class="rounded-xl border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500">
                            Belum ada item.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="(item, idx) in form.items" :key="idx" class="grid items-end gap-2 md:grid-cols-12">
                                <div class="md:col-span-8">
                                    <Label class="text-xs">ID Produk</Label>
                                    <Input v-model="item.pos_product_id" type="number" min="1" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-3">
                                    <Label class="text-xs">Qty</Label>
                                    <Input v-model="item.quantity" type="number" min="1" class="mt-1 rounded-lg" />
                                </div>
                                <div class="md:col-span-1">
                                    <Button type="button" variant="ghost" size="sm" class="text-red-600" @click="removeItem(idx)">×</Button>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link href="/cooperative/pos/inventory/transfers">
                            <Button type="button" variant="outline" class="rounded-xl">Batal</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing" class="rounded-xl">Simpan</Button>
                    </div>
                </form>
            </div>
        </PageContainer>
    </AppLayout>
</template>
