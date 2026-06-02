<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Circle, PlayCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type OnboardingStep = {
    key: string;
    label: string;
    description: string;
    href: string;
    completed: boolean;
};

const props = defineProps<{
    onboarding: {
        completed_steps: number;
        total_steps: number;
        progress_percent: number;
        is_complete: boolean;
        is_dismissed: boolean;
        steps: OnboardingStep[];
    };
    compact?: boolean;
}>();

const markStep = (step: string): void => {
    router.post('/member/onboarding/steps', { step }, { preserveScroll: true });
};
</script>

<template>
    <section v-if="!onboarding.is_complete && !onboarding.is_dismissed" class="rounded-lg border p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold">Mulai Kojayaku</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ onboarding.completed_steps }}/{{ onboarding.total_steps }} langkah selesai</p>
            </div>
            <Button as-child variant="outline" size="sm">
                <Link href="/member/onboarding">Buka onboarding</Link>
            </Button>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
            <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${onboarding.progress_percent}%` }" />
        </div>

        <div class="mt-4 grid gap-3" :class="compact ? 'md:grid-cols-1' : 'md:grid-cols-2 xl:grid-cols-3'">
            <div v-for="step in onboarding.steps" :key="step.key" class="flex gap-3 rounded-md border p-3">
                <CheckCircle2 v-if="step.completed" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                <Circle v-else class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium">{{ step.label }}</div>
                    <div class="mt-1 text-xs text-muted-foreground">{{ step.description }}</div>
                    <div v-if="!step.completed" class="mt-3 flex flex-wrap gap-2">
                        <Button as-child size="sm" variant="outline">
                            <Link :href="step.href"><PlayCircle class="h-4 w-4" /> Lanjut</Link>
                        </Button>
                        <Button v-if="step.key === 'loans' || step.key === 'rewards'" size="sm" variant="ghost" @click="markStep(step.key)">Tandai selesai</Button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
