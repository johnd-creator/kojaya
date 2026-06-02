<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import OnboardingChecklist from '@/components/Kojayaku/OnboardingChecklist.vue';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    member: { name: string; member_no: string; organization?: { name: string } | null };
    onboarding: {
        completed_steps: number;
        total_steps: number;
        progress_percent: number;
        is_complete: boolean;
        is_dismissed: boolean;
        steps: Array<{ key: string; label: string; description: string; href: string; completed: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Onboarding Kojayaku" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Onboarding', href: '/member/onboarding' }]">
        <PageContainer>
            <div class="rounded-lg border p-5">
                <p class="text-sm text-muted-foreground">{{ member.member_no }} · {{ member.organization?.name || 'Koperasi' }}</p>
                <h1 class="mt-1 text-2xl font-semibold">Onboarding {{ member.name }}</h1>
            </div>
            <OnboardingChecklist :onboarding="onboarding" />
            <div v-if="onboarding.is_complete" class="rounded-lg border p-5">
                <h2 class="font-semibold">Onboarding selesai</h2>
                <p class="mt-1 text-sm text-muted-foreground">Semua langkah awal Kojayaku sudah selesai.</p>
            </div>
        </PageContainer>
    </AppLayout>
</template>
