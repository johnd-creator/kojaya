<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Search, Wallet, ArrowRight, Plus } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, show, store } from "@/routes/cooperative/store-credit";
import type { PaginatedResource } from "@/types";

type Account = {
    id: number;
    balance: number;
    credit_limit: number;
    available_credit: number;
    status: string;
    status_label: string;
    is_negative: boolean;
    member: { id: number; full_name: string | null; name: string | null; member_no: string | null } | null;
};

type EligibleMember = {
    id: number;
    organization_id: string;
    organization_code: string | null;
    organization_name: string | null;
    member_no: string | null;
    name: string | null;
};

const props = defineProps<{
    accounts: PaginatedResource<Account>;
    filters: { q?: string; filter?: string };
    eligibleMembers: EligibleMember[];
    canManage: boolean;
}>();

const balanceTone = (account: Account): string =>
    account.balance > 0 ? "text-emerald-600" : account.balance < 0 ? "text-rose-600" : "text-zinc-500";

const filters = computed(() => props.filters);

function applyFilter(): void {
    router.get(index.url(), filters.value, { preserveState: true, preserveScroll: true, replace: true });
}

const showOpenDialog = ref(false);

const openForm = useForm({
    cooperative_member_id: "",
    credit_limit: 0,
    opening_balance: 0,
    reason: "",
});

function submitOpenAccount(): void {
    openForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            showOpenDialog.value = false;
            openForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Saldo Toko Anggota" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '#' },
            { title: 'Saldo Toko Anggota', href: index.url() },
        ]"
    >
        <PageContainer class="max-w-6xl">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Saldo Toko Anggota</h1>
                    <p class="text-sm text-zinc-500">Kelola saldo toko anggota</p>
                </div>
                <div class="flex items-center gap-2">
                    <Dialog v-if="canManage" v-model:open="showOpenDialog">
                        <DialogTrigger as-child>
                            <Button><Plus class="mr-2 size-4" /> Buka Akun</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Buka Akun Saldo Toko</DialogTitle>
                                <DialogDescription>
                                    Pilih anggota untuk mengaktifkan akun Saldo Toko. Setelah dibuka, anggota dapat
                                    menerima setoran dan membayar transaksi menggunakan saldo.
                                </DialogDescription>
                            </DialogHeader>
                            <form class="space-y-4" @submit.prevent="submitOpenAccount">
                                <div class="space-y-2">
                                    <Label>Anggota</Label>
                                    <select
                                        v-model="openForm.cooperative_member_id"
                                        required
                                        class="h-9 w-full rounded-md border bg-white px-2 text-sm dark:bg-zinc-950"
                                    >
                                        <option value="">Pilih anggota</option>
                                        <option
                                            v-for="member in eligibleMembers"
                                            :key="member.id"
                                            :value="member.id"
                                        >
                                            {{ member.organization_code ?? member.organization_name ?? 'Koperasi' }} —
                                            {{ member.member_no ?? '-' }} — {{ member.name ?? 'Anggota' }}
                                        </option>
                                    </select>
                                    <p v-if="eligibleMembers.length === 0" class="text-xs text-zinc-500">
                                        Semua anggota aktif sudah memiliki akun Saldo Toko.
                                    </p>
                                    <p v-if="openForm.errors.cooperative_member_id" class="text-xs text-red-500">
                                        {{ openForm.errors.cooperative_member_id }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <Label>Limit Kredit (opsional)</Label>
                                        <Input v-model.number="openForm.credit_limit" type="number" min="0" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Saldo Awal (opsional)</Label>
                                        <Input v-model.number="openForm.opening_balance" type="number" min="0" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label>Keterangan (opsional)</Label>
                                    <Textarea v-model="openForm.reason" />
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="showOpenDialog = false">
                                        Batal
                                    </Button>
                                    <Button type="submit" :disabled="openForm.processing">Buka Akun</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Wallet class="size-8 text-indigo-600" />
                </div>
            </div>

            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative flex-1 min-w-[200px]">
                            <Search class="absolute left-2 top-2.5 size-4 text-zinc-400" />
                            <Input
                                v-model="filters.q"
                                placeholder="Cari anggota..."
                                class="pl-8"
                                @keyup.enter="applyFilter"
                            />
                        </div>
                        <select v-model="filters.filter" class="h-9 rounded-md border px-2 text-sm" @change="applyFilter">
                            <option value="">Semua</option>
                            <option value="positive">Saldo Positif</option>
                            <option value="negative">Saldo Negatif</option>
                            <option value="zero">Saldo Nol</option>
                            <option value="suspended">Ditangguhkan</option>
                        </select>
                        <Button variant="outline" size="sm" @click="applyFilter">Terapkan</Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="p-3">Anggota</th>
                                <th class="p-3 text-right">Saldo</th>
                                <th class="p-3 text-right">Limit Kredit</th>
                                <th class="p-3">Status</th>
                                <th class="p-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="account in accounts.data" :key="account.id" class="border-b last:border-0">
                                <td class="p-3 font-medium">
                                    {{ account.member?.full_name ?? account.member?.name ?? `Anggota #${account.member?.id}` }}
                                </td>
                                <td class="p-3 text-right font-mono font-semibold" :class="balanceTone(account)">
                                    {{ formatCurrency(account.balance) }}
                                </td>
                                <td class="p-3 text-right font-mono text-zinc-600">
                                    {{ formatCurrency(account.credit_limit) }}
                                </td>
                                <td class="p-3">
                                    <Badge :variant="account.status === 'active' ? 'default' : 'secondary'">
                                        {{ account.status_label }}
                                    </Badge>
                                </td>
                                <td class="p-3 text-right">
                                    <Link :href="show({ account: account.id }).url" class="inline-flex items-center text-indigo-600 hover:underline">
                                        Detail <ArrowRight class="ml-1 size-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="accounts.data.length === 0">
                                <td colspan="5" class="p-6 text-center text-zinc-500">Belum ada akun saldo toko.</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <div class="flex items-center justify-center gap-2 py-4">
                <Link
                    v-for="(link, i) in accounts.meta.links"
                    :key="i"
                    :href="link.url || '#'"
                    :class="['rounded px-3 py-1 text-sm', link.active ? 'bg-indigo-600 text-white' : 'hover:bg-zinc-100', !link.url && 'pointer-events-none opacity-40']"
                    v-html="link.label"
                />
            </div>
        </PageContainer>
    </AppLayout>
</template>
