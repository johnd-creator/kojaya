<script setup lang="ts">
import { CheckCircle2, Download, FileText, Loader2, XCircle } from "lucide-vue-next";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import Progress from "@/components/ui/progress/Progress.vue";
import ProgressIndicator from "@/components/ui/progress/ProgressIndicator.vue";
import type { BackgroundJobState } from "@/composables/useBackgroundJob";

const props = defineProps<{
    state: BackgroundJobState;
    submitting: boolean;
    error: string | null;
    label?: string;
}>();

defineEmits<{
    (event: "retry"): void;
    (event: "reset"): void;
}>();

const status = computed(() => props.state.status);
const progress = computed(() => Math.max(0, Math.min(100, props.state.progress ?? 0)));
const isPending = computed(() => status.value === "pending");
const isProcessing = computed(() => status.value === "processing");
const isCompleted = computed(() => status.value === "completed");
const isFailed = computed(() => status.value === "failed");
const showTracker = computed(
    () => props.submitting || isPending.value || isProcessing.value || isCompleted.value || isFailed.value,
);

const headline = computed(() => {
    if (props.submitting || isPending.value) {
        return "Mengantri pekerjaan…";
    }
    if (isProcessing.value) {
        return "Menyusun laporan PDF…";
    }
    if (isCompleted.value) {
        return "Laporan PDF siap diunduh";
    }
    if (isFailed.value) {
        return "Pekerjaan gagal";
    }
    return "";
});

const description = computed(() => {
    if (props.error) {
        return props.error;
    }
    if (isFailed.value && props.state.errorMessage) {
        return props.state.errorMessage;
    }
    if (isCompleted.value && props.state.originalName) {
        return props.state.originalName;
    }
    if (isProcessing.value) {
        return "Jangan tutup halaman. Anda akan mendapat notifikasi saat file siap.";
    }
    return "";
});
</script>

<template>
    <div
        v-if="showTracker"
        class="flex flex-col gap-3 rounded-2xl border border-zinc-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        :aria-busy="isProcessing || isPending"
        role="status"
    >
        <div class="flex items-center gap-3">
            <span
                v-if="isCompleted"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
            >
                <CheckCircle2 class="h-5 w-5" />
            </span>
            <span
                v-else-if="isFailed"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300"
            >
                <XCircle class="h-5 w-5" />
            </span>
            <span
                v-else
                class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
            >
                <Loader2 class="h-5 w-5 animate-spin" />
            </span>
            <div class="flex-1">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ label ?? "Laporan POS" }}
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ headline }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a v-if="isCompleted && state.downloadUrl" :href="state.downloadUrl">
                    <Button size="sm" class="rounded-xl">
                        <Download class="mr-2 h-4 w-4" /> Unduh PDF
                    </Button>
                </a>
                <Button
                    v-if="isFailed"
                    size="sm"
                    variant="outline"
                    class="rounded-xl"
                    @click="$emit('retry')"
                >
                    <FileText class="mr-2 h-4 w-4" /> Coba lagi
                </Button>
            </div>
        </div>
        <Progress :model-value="progress" :max="100" class="h-2">
            <ProgressIndicator
                class="h-full w-full flex-1 transition-all"
                :class="isFailed ? 'bg-red-500' : 'bg-blue-500'"
                :style="`transform: translateX(-${100 - progress}%)`"
            />
        </Progress>
        <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400">{{ description }}</p>
    </div>
</template>
