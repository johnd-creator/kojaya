<script setup lang="ts">
defineOptions({ inheritAttrs: false });

defineProps<{
  message?: string;
}>();
</script>

<template>
  <div v-show="message" v-bind="$attrs">
    <p class="text-sm text-red-600 dark:text-red-500">
      {{ message }}
    </p>
  </div>
</template>
