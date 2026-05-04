<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    rewards: {
        data: Array<{
            id: string;
            name: string;
            category: string;
            description: string | null;
            points_required: number;
            stock: number | null;
            valid_until: string | null;
            is_active: boolean;
        }>;
    };
}>();

const form = reactive({
    name: '',
    category: 'BARANG',
    description: '',
    points_required: 100,
    stock: 10,
    valid_until: '',
    image_url: '',
    is_active: true,
});

const submit = (): void => {
    router.post('/cooperative/rewards', form);
};
</script>

<template>
    <Head title="Rewards" />

    <AppLayout :breadcrumbs="[{ title: 'Cooperative', href: '/cooperative/members' }, { title: 'Rewards', href: '/cooperative/rewards' }]">
        <PageContainer>
            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3">Reward</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Points</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Valid Until</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reward in props.rewards.data" :key="reward.id" class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ reward.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ reward.description || '-' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ reward.category }}</td>
                                <td class="px-4 py-3">{{ reward.points_required }}</td>
                                <td class="px-4 py-3">{{ reward.stock ?? 'Unlimited' }}</td>
                                <td class="px-4 py-3">{{ reward.valid_until || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold">Tambah Reward</h2>
                    <div class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <Label for="reward-name">Nama Reward</Label>
                            <Input id="reward-name" v-model="form.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="reward-category">Kategori</Label>
                            <select id="reward-category" v-model="form.category" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="BARANG">BARANG</option>
                                <option value="DISKON">DISKON</option>
                                <option value="LAYANAN">LAYANAN</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="reward-points">Poin Dibutuhkan</Label>
                            <Input id="reward-points" v-model.number="form.points_required" type="number" min="1" />
                        </div>
                        <div class="space-y-2">
                            <Label for="reward-stock">Stok</Label>
                            <Input id="reward-stock" v-model.number="form.stock" type="number" min="0" />
                        </div>
                        <div class="space-y-2">
                            <Label for="reward-until">Valid Sampai</Label>
                            <Input id="reward-until" v-model="form.valid_until" type="date" />
                        </div>
                        <Button class="w-full" @click="submit">Simpan Reward</Button>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
