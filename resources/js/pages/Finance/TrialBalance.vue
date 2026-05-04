<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

const props = defineProps<{ rows: Array<{ account_id: string; code: string; name: string; account_type: string; debit: number; credit: number; balance: number }>; filters: { as_of_date?: string } }>();
const form = reactive({ as_of_date: props.filters.as_of_date ?? '' });
const submit = (): void => router.get('/finance/trial-balance', form, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Trial Balance" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Trial Balance', href: '/finance/trial-balance' }]">
        <PageContainer>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Trial Balance</h1>
                    <p class="text-sm text-muted-foreground">Ringkasan saldo seluruh akun pada tanggal tertentu.</p>
                </div>
                <div class="flex gap-2">
                    <Input v-model="form.as_of_date" type="date" class="w-48" />
                    <Button variant="outline" @click="submit">Terapkan</Button>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3 text-right">Debit</th>
                            <th class="px-4 py-3 text-right">Credit</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.account_id" class="border-t">
                            <td class="px-4 py-3">{{ row.code }} - {{ row.name }}</td>
                            <td class="px-4 py-3">{{ row.account_type }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(row.debit) }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(row.credit) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(row.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
