<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    user: { name: string; email: string };
    employee: { birth_date: string | null; gender: string | null; employee_code: string };
}>();

const form = reactive({
    name: props.user.name,
    email: props.user.email,
    birth_date: props.employee.birth_date ?? '',
    gender: props.employee.gender ?? '',
});

const submit = (): void => {
    router.put('/ess/profile', form);
};
</script>

<template>
    <Head title="ESS Profile" />

    <AppLayout :breadcrumbs="[{ title: 'ESS', href: '/ess' }, { title: 'Profile', href: '/ess/profile' }]">
        <PageContainer variant="form">
            <div>
                <h1 class="text-2xl font-semibold">Profil Saya</h1>
                <p class="text-sm text-muted-foreground">Perbarui data dasar ESS untuk akun karyawan.</p>
            </div>

            <div class="space-y-4 rounded-lg border p-6">
                <div class="space-y-2">
                    <Label for="name">Nama</Label>
                    <Input id="name" v-model="form.name" />
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" />
                </div>
                <div class="space-y-2">
                    <Label for="birth-date">Tanggal Lahir</Label>
                    <Input id="birth-date" v-model="form.birth_date" type="date" />
                </div>
                <div class="space-y-2">
                    <Label for="gender">Gender</Label>
                    <select id="gender" v-model="form.gender" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                        <option value="">Pilih</option>
                        <option value="MALE">MALE</option>
                        <option value="FEMALE">FEMALE</option>
                    </select>
                </div>
                <Button @click="submit">Simpan Perubahan</Button>
            </div>
        </PageContainer>
    </AppLayout>
</template>
