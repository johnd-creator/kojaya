<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

const props = defineProps<{ statement: { assets: Array<any>; liabilities: Array<any>; equity: Array<any> }; filters: { as_of_date?: string } }>();
const form = reactive({ as_of_date: props.filters.as_of_date ?? '' });
const submit = (): void => router.get('/finance/balance-sheet', form, { preserveState: true, replace: true });
const total = (rows: Array<{ balance: number }>): number => rows.reduce((sum, row) => sum + Number(row.balance ?? 0), 0);
</script>

<template>
    <Head title="Balance Sheet" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Balance Sheet', href: '/finance/balance-sheet' }]">
        <PageContainer>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Neraca</h1>
                    <p class="text-sm text-muted-foreground">Posisi aset, kewajiban, dan ekuitas organisasi.</p>
                </div>
                <div class="flex gap-2">
                    <Input v-model="form.as_of_date" type="date" class="w-48" />
                    <Button variant="outline" @click="submit">Terapkan</Button>
                </div>
            </div>
            <div class="grid gap-6 xl:grid-cols-3">
                <section class="rounded-lg border p-4">
                    <h2 class="font-semibold">Aset</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="row in statement.assets" :key="row.account_id" class="flex items-center justify-between gap-3">
                            <span>{{ row.code }} - {{ row.name }}</span>
                            <span class="font-medium">{{ formatCurrency(row.balance) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3 font-semibold">Total {{ formatCurrency(total(statement.assets)) }}</div>
                </section>
                <section class="rounded-lg border p-4">
                    <h2 class="font-semibold">Liabilitas</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="row in statement.liabilities" :key="row.account_id" class="flex items-center justify-between gap-3">
                            <span>{{ row.code }} - {{ row.name }}</span>
                            <span class="font-medium">{{ formatCurrency(row.balance) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3 font-semibold">Total {{ formatCurrency(total(statement.liabilities)) }}</div>
                </section>
                <section class="rounded-lg border p-4">
                    <h2 class="font-semibold">Ekuitas</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="row in statement.equity" :key="row.account_id" class="flex items-center justify-between gap-3">
                            <span>{{ row.code }} - {{ row.name }}</span>
                            <span class="font-medium">{{ formatCurrency(row.balance) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3 font-semibold">Total {{ formatCurrency(total(statement.equity)) }}</div>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
