<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Wallet } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDateTime } from "@/lib/formatters";
import {
    adjust,
    cashFunding,
    limit as changeLimit,
    reactivate,
    show,
    suspend,
} from "@/routes/cooperative/store-credit";
import { store as storeDelegate } from "@/routes/cooperative/store-credit/delegates";

type LedgerEntry = {
    id: number;
    entry_type: string;
    entry_type_label: string;
    amount: number;
    effect: string;
    balance_before: number;
    balance_after: number;
    reason: string | null;
    is_reversed: boolean;
    occurred_at: string | null;
};

const props = defineProps<{
    account: {
        id: number;
        balance: number;
        credit_limit: number;
        available_credit: number;
        status: string;
        status_label: string;
        is_negative: boolean;
        member: { id: number; full_name: string | null } | null;
    };
    ledger: { data: LedgerEntry[]; links: any[] };
    delegates: any[];
}>();

const balanceTone = (): string =>
    props.account.balance > 0 ? "text-emerald-600" : props.account.balance < 0 ? "text-rose-600" : "text-zinc-500";

const cashForm = useForm({ amount: 0, reference_no: "" });
const limitForm = useForm({ credit_limit: props.account.credit_limit, reason: "", override_below_debt: false });
const adjustForm = useForm({ amount: 0, effect: "credit", reason: "" });
const delegateForm = useForm({ display_name: "", pin: "", per_transaction_limit: null as number | null, daily_limit: null as number | null });

function submitCash(): void {
    cashForm.post(cashFunding({ account: props.account.id }).url, { preserveScroll: true, onSuccess: () => cashForm.reset() });
}
function submitLimit(): void {
    limitForm.post(changeLimit({ account: props.account.id }).url, { preserveScroll: true });
}
function submitAdjust(): void {
    adjustForm.post(adjust({ account: props.account.id }).url, { preserveScroll: true, onSuccess: () => adjustForm.reset() });
}
function submitDelegate(): void {
    delegateForm.post(storeDelegate({ account: props.account.id }).url, { preserveScroll: true, onSuccess: () => delegateForm.reset() });
}
</script>

<template>
    <Head title="Detail Saldo Toko" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '#' },
            { title: 'Saldo Toko Anggota', href: show({ account: account.id }).url },
        ]"
    >
        <PageContainer class="max-w-6xl">
            <Link href="/cooperative/store-credit" class="inline-flex items-center text-sm text-indigo-600 hover:underline">
                <ArrowLeft class="mr-1 size-4" /> Kembali
            </Link>

            <div class="mt-2 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ account.member?.full_name ?? 'Anggota' }}</h1>
                    <p class="text-sm text-zinc-500">Member Store Credit Ledger</p>
                </div>
                <div class="flex gap-2">
                    <form v-if="account.status === 'active'" :action="suspend({ account: account.id }).url" method="post">
                        <Button type="submit" formmethod="post" variant="outline" size="sm">Tangguhkan</Button>
                    </form>
                    <form v-else-if="account.status === 'suspended'" :action="reactivate({ account: account.id }).url" method="post">
                        <Button type="submit" variant="outline" size="sm">Aktifkan</Button>
                    </form>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Saldo</div>
                        <div class="text-2xl font-bold font-mono" :class="balanceTone()">{{ formatCurrency(account.balance) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Limit Kredit</div>
                        <div class="text-2xl font-bold font-mono">{{ formatCurrency(account.credit_limit) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Kredit Tersedia</div>
                        <div class="text-2xl font-bold font-mono text-indigo-600">{{ formatCurrency(account.available_credit) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Status</div>
                        <div class="mt-1"><Badge :variant="account.status === 'active' ? 'default' : 'secondary'">{{ account.status_label }}</Badge></div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>Setoran Tunai</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <form class="space-y-2" @submit.prevent="submitCash">
                            <div>
                                <Label>Jumlah (Rupiah)</Label>
                                <Input v-model.number="cashForm.amount" type="number" min="1" />
                            </div>
                            <div>
                                <Label>No. Referensi</Label>
                                <Input v-model="cashForm.reference_no" />
                            </div>
                            <Button type="submit" :disabled="cashForm.processing">Posting Setoran</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Ubah Limit Kredit</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <form class="space-y-2" @submit.prevent="submitLimit">
                            <div>
                                <Label>Limit Baru (Rupiah)</Label>
                                <Input v-model.number="limitForm.credit_limit" type="number" min="0" />
                            </div>
                            <div>
                                <Label>Alasan</Label>
                                <Textarea v-model="limitForm.reason" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="limitForm.override_below_debt" type="checkbox" /> Override di bawah utang
                            </label>
                            <Button type="submit" :disabled="limitForm.processing">Simpan Limit</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Penyesuaian Saldo</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <form class="space-y-2" @submit.prevent="submitAdjust">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label>Jumlah</Label>
                                    <Input v-model.number="adjustForm.amount" type="number" min="1" />
                                </div>
                                <div>
                                    <Label>Arah</Label>
                                    <select v-model="adjustForm.effect" class="h-9 w-full rounded-md border px-2 text-sm">
                                        <option value="credit">Kredit (Tambah)</option>
                                        <option value="debit">Debit (Kurangi)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <Label>Alasan</Label>
                                <Textarea v-model="adjustForm.reason" />
                            </div>
                            <Button type="submit" :disabled="adjustForm.processing">Posting Penyesuaian</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Delegate / Staff</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <ul class="space-y-1 text-sm">
                            <li v-for="delegate in delegates" :key="delegate.id" class="flex items-center justify-between rounded border p-2">
                                <span>{{ delegate.display_name }} · <code>{{ delegate.code }}</code></span>
                                <Badge :variant="delegate.is_currently_active ? 'default' : 'secondary'">{{ delegate.status_label }}</Badge>
                            </li>
                            <li v-if="delegates.length === 0" class="text-zinc-500">Belum ada delegate.</li>
                        </ul>
                        <form class="grid grid-cols-2 gap-2" @submit.prevent="submitDelegate">
                            <Input v-model="delegateForm.display_name" placeholder="Nama delegate" />
                            <Input v-model="delegateForm.pin" type="password" placeholder="PIN" />
                            <Button type="submit" :disabled="delegateForm.processing">Tambah Delegate</Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle class="flex items-center gap-2"><Wallet class="size-4" /> Riwayat Ledger</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="p-3">Waktu</th>
                                <th class="p-3">Jenis</th>
                                <th class="p-3 text-right">Jumlah</th>
                                <th class="p-3 text-right">Saldo Setelah</th>
                                <th class="p-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in ledger.data" :key="entry.id" class="border-b last:border-0">
                                <td class="p-3">{{ formatDateTime(entry.occurred_at) }}</td>
                                <td class="p-3">
                                    {{ entry.entry_type_label }}
                                    <Badge v-if="entry.is_reversed" variant="secondary" class="ml-1">Dibatalkan</Badge>
                                </td>
                                <td class="p-3 text-right font-mono" :class="entry.effect === 'credit' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ entry.effect === 'credit' ? '+' : '-' }}{{ formatCurrency(entry.amount) }}
                                </td>
                                <td class="p-3 text-right font-mono font-semibold">{{ formatCurrency(entry.balance_after) }}</td>
                                <td class="p-3 text-zinc-600">{{ entry.reason }}</td>
                            </tr>
                            <tr v-if="ledger.data.length === 0">
                                <td colspan="5" class="p-6 text-center text-zinc-500">Belum ada ledger entry.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex items-center justify-center gap-2 p-3">
                        <Link
                            v-for="(link, i) in ledger.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="['rounded px-3 py-1 text-sm', link.active ? 'bg-indigo-600 text-white' : 'hover:bg-zinc-100', !link.url && 'pointer-events-none opacity-40']"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </PageContainer>
    </AppLayout>
</template>
