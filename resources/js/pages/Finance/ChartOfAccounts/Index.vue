<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    accounts: { data: Array<{ id: string; code: string; name: string; account_type: string; normal_balance: string; category: string; parent?: { name: string } | null; is_active: boolean }> };
    filters: { account_type?: string };
}>();

const form = reactive({
    code: '',
    name: '',
    account_type: 'ASSET',
    normal_balance: 'DEBIT',
    category: 'CURRENT',
    description: '',
    is_active: true,
});

const submit = (): void => {
    router.post('/finance/chart-of-accounts', form);
};
</script>

<template>
    <Head title="Chart of Accounts" />

    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Chart of Accounts', href: '/finance/chart-of-accounts' }]">
        <PageContainer>
            <div class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3">
                        <h1 class="text-xl font-semibold">Chart of Accounts</h1>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Tipe</th>
                                    <th class="px-4 py-3">Saldo Normal</th>
                                    <th class="px-4 py-3">Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in accounts.data" :key="account.id" class="border-t">
                                    <td class="px-4 py-3 font-medium">{{ account.code }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ account.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ account.parent?.name || 'Akun induk' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ account.account_type }}</td>
                                    <td class="px-4 py-3">{{ account.normal_balance }}</td>
                                    <td class="px-4 py-3">{{ account.category }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold">Tambah Akun</h2>
                    <div class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <Label for="account-code">Kode</Label>
                            <Input id="account-code" v-model="form.code" />
                        </div>
                        <div class="space-y-2">
                            <Label for="account-name">Nama</Label>
                            <Input id="account-name" v-model="form.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="account-type">Tipe</Label>
                            <select id="account-type" v-model="form.account_type" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="ASSET">ASSET</option>
                                <option value="LIABILITY">LIABILITY</option>
                                <option value="EQUITY">EQUITY</option>
                                <option value="REVENUE">REVENUE</option>
                                <option value="EXPENSE">EXPENSE</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="normal-balance">Saldo Normal</Label>
                            <select id="normal-balance" v-model="form.normal_balance" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="DEBIT">DEBIT</option>
                                <option value="CREDIT">CREDIT</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="account-category">Kategori</Label>
                            <Input id="account-category" v-model="form.category" />
                        </div>
                        <div class="space-y-2">
                            <Label for="account-description">Deskripsi</Label>
                            <textarea id="account-description" v-model="form.description" class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"></textarea>
                        </div>
                        <Button class="w-full" @click="submit">Simpan Akun</Button>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
