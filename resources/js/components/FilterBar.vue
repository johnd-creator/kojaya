<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

withDefaults(
  defineProps<{
    searchPlaceholder?: string;
    showSearch?: boolean;
    showApply?: boolean;
    showReset?: boolean;
  }>(),
  {
    searchPlaceholder: "Cari data...",
    showSearch: true,
    showApply: false,
    showReset: true,
  },
);

const search = defineModel<string>("search", { default: "" });

const emit = defineEmits<{
  submit: [];
  reset: [];
}>();
</script>

<template>
  <form
    class="flex flex-col gap-3 rounded-lg border bg-card p-3 sm:flex-row sm:items-center"
    @submit.prevent="emit('submit')"
  >
    <Input
      v-if="showSearch"
      v-model="search"
      type="search"
      :placeholder="searchPlaceholder"
      class="sm:max-w-xs"
    />

    <div class="flex flex-1 flex-wrap items-center gap-3">
      <slot />
    </div>

    <div class="flex items-center gap-2" v-if="showApply || showReset">
      <Button v-if="showApply" type="submit" variant="outline">Terapkan</Button>
      <Button
        v-if="showReset"
        type="button"
        variant="ghost"
        @click="emit('reset')"
        >Reset</Button
      >
    </div>
  </form>
</template>
