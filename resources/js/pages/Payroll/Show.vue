<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";

defineProps<{
  payroll: any;
}>();
</script>

<template>
  <Head title="Detail Payroll" />
  <PageContainer>
    <div class="p-6">
      <h1 class="text-2xl font-bold">Detail Payroll</h1>
    </div>
  </PageContainer>
</template>
