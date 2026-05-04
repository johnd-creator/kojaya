<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    user: { name: string; email: string };
    member: { phone?: string | null; address?: string | null; member_no: string };
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.member.phone ?? '',
    address: props.member.address ?? '',
});

const submit = (): void => {
    form.put('/member/profile');
};
</script>

<template>
    <Head title="Profil Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Profil', href: '/member/profile' }]">
        <PageContainer variant="form">
            <div>
                <h1 class="text-2xl font-semibold">Profil Anggota</h1>
                <p class="text-sm text-muted-foreground">Nomor anggota {{ member.member_no }}</p>
            </div>
            <div class="space-y-4 rounded-lg border p-6">
                <div class="space-y-2">
                    <Label for="member-name">Nama</Label>
                    <Input id="member-name" v-model="form.name" />
                </div>
                <div class="space-y-2">
                    <Label for="member-email">Email</Label>
                    <Input id="member-email" v-model="form.email" type="email" />
                </div>
                <div class="space-y-2">
                    <Label for="member-phone">Telepon</Label>
                    <Input id="member-phone" v-model="form.phone" />
                </div>
                <div class="space-y-2">
                    <Label for="member-address">Alamat</Label>
                    <textarea id="member-address" v-model="form.address" class="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm"></textarea>
                </div>
                <Button @click="submit">Simpan Perubahan</Button>
            </div>
        </PageContainer>
    </AppLayout>
</template>
