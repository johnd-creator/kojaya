<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

const props = defineProps<{
    entries: { data: Array<{ id: string; journal_number: string; entry_date: string; status: string; reference_number?: string | null; description: string; posted_by?: { name: string } | null; lines: Array<{ id: string; account?: { code: string; name: string } | null; debit: number | string; credit: number | string }> }> };
    accounts: Array<{ id: string; code: string; name: string; normal_balance: string }>;
}>();

const form = reactive({
    entry_date: new Date().toISOString().slice(0, 10),
    status: 'POSTED',
    reference_number: '',
    description: '',
    lines: [
        { chart_of_account_id: props.accounts[0]?.id ?? '', debit: 0, credit: 0, memo: '' },
        { chart_of_account_id: props.accounts[1]?.id ?? props.accounts[0]?.id ?? '', debit: 0, credit: 0, memo: '' },
    ],
});

const addLine = (): void => {
    form.lines.push({ chart_of_account_id: props.accounts[0]?.id ?? '', debit: 0, credit: 0, memo: '' });
};

const submit = (): void => {
    router.post('/finance/journal-entries', form);
};
</script>

<template>
    <Head title="Journal Entries" />

    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Journal Entries', href: '/finance/journal-entries' }]">
        <PageContainer>
            <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3">
                        <h1 class="text-xl font-semibold">Jurnal Umum</h1>
                    </div>
                    <div class="space-y-4 p-4">
                        <div v-for="entry in entries.data" :key="entry.id" class="rounded-lg border p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ entry.journal_number }}</div>
                                    <div class="text-sm text-muted-foreground">{{ formatDate(entry.entry_date) }} · {{ entry.description }}</div>
                                </div>
                                <div class="text-sm">{{ entry.status }}</div>
                            </div>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="text-xs uppercase text-muted-foreground">
                                        <tr>
                                            <th class="py-2">Akun</th>
                                            <th class="py-2 text-right">Debit</th>
                                            <th class="py-2 text-right">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="line in entry.lines" :key="line.id" class="border-t">
                                            <td class="py-2">{{ line.account?.code }} - {{ line.account?.name }}</td>
                                            <td class="py-2 text-right">{{ formatCurrency(line.debit) }}</td>
                                            <td class="py-2 text-right">{{ formatCurrency(line.credit) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold">Buat Jurnal Baru</h2>
                    <div class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <Label for="entry-date">Tanggal</Label>
                            <Input id="entry-date" v-model="form.entry_date" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="reference-number">Referensi</Label>
                            <Input id="reference-number" v-model="form.reference_number" />
                        </div>
                        <div class="space-y-2">
                            <Label for="entry-description">Deskripsi</Label>
                            <textarea id="entry-description" v-model="form.description" class="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <Label>Baris Jurnal</Label>
                                <Button type="button" variant="outline" size="sm" @click="addLine">Tambah Baris</Button>
                            </div>
                            <div v-for="(line, index) in form.lines" :key="index" class="rounded-md border p-3 space-y-2">
                                <select v-model="line.chart_of_account_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.code }} - {{ account.name }}</option>
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <Input v-model.number="line.debit" type="number" min="0" step="0.01" placeholder="Debit" />
                                    <Input v-model.number="line.credit" type="number" min="0" step="0.01" placeholder="Credit" />
                                </div>
                                <Input v-model="line.memo" placeholder="Memo" />
                            </div>
                        </div>
                        <Button class="w-full" @click="submit">Simpan Jurnal</Button>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
