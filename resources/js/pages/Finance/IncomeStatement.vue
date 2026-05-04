<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

const props = defineProps<{ statement: { revenues: Array<any>; expenses: Array<any>; net_income: number }; filters: { start_date?: string; end_date?: string } }>();
const form = reactive({ start_date: props.filters.start_date ?? '', end_date: props.filters.end_date ?? '' });
const submit = (): void => router.get('/finance/income-statement', form, { preserveState: true, replace: true });
const total = (rows: Array<{ balance: number }>): number => rows.reduce((sum, row) => sum + Number(row.balance ?? 0), 0);
</script>

<template>
    <Head title="Income Statement" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Income Statement', href: '/finance/income-statement' }]">
        <PageContainer>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Laba Rugi</h1>
                    <p class="text-sm text-muted-foreground">Ringkasan pendapatan dan biaya pada periode tertentu.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Input v-model="form.start_date" type="date" class="w-44" />
                    <Input v-model="form.end_date" type="date" class="w-44" />
                    <Button variant="outline" @click="submit">Terapkan</Button>
                </div>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-lg border p-4">
                    <h2 class="font-semibold">Pendapatan</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="row in statement.revenues" :key="row.account_id" class="flex items-center justify-between gap-3">
                            <span>{{ row.code }} - {{ row.name }}</span>
                            <span class="font-medium">{{ formatCurrency(row.balance) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3 font-semibold">Total {{ formatCurrency(total(statement.revenues)) }}</div>
                </section>
                <section class="rounded-lg border p-4">
                    <h2 class="font-semibold">Biaya</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="row in statement.expenses" :key="row.account_id" class="flex items-center justify-between gap-3">
                            <span>{{ row.code }} - {{ row.name }}</span>
                            <span class="font-medium">{{ formatCurrency(row.balance) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3 font-semibold">Total {{ formatCurrency(total(statement.expenses)) }}</div>
                </section>
            </div>
            <div class="rounded-lg border bg-primary/5 p-4 text-lg font-semibold">
                Laba Bersih: {{ formatCurrency(statement.net_income) }}
            </div>
        </PageContainer>
    </AppLayout>
</template>
