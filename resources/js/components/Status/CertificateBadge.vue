<script setup lang="ts">
import { computed } from 'vue';
import type { CertificateStatus } from '@/types';

type Props = {
    status: CertificateStatus;
    size?: 'sm' | 'md' | 'lg';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const badgeConfig = computed(() => {
    const configs = {
        VALID: {
            classes: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            label: 'Valid',
            icon: 'CheckCircle',
        },
        EXPIRING: {
            classes: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
            label: 'Expiring Soon',
            icon: 'AlertTriangle',
        },
        EXPIRED: {
            classes: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
            label: 'Expired',
            icon: 'XCircle',
        },
        REVOKED: {
            classes: 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
            label: 'Revoked',
            icon: 'Ban',
        },
    };

    return configs[props.status] || configs.VALID;
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
        lg: 'px-3 py-1.5 text-base',
    };

    return sizes[props.size];
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full font-medium"
        :class="[badgeConfig.classes, sizeClasses]"
    >
        <span>{{ badgeConfig.label }}</span>
    </span>
</template>
