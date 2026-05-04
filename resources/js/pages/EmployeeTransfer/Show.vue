<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  transfer: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "#" },
  { title: "Employee Transfers", href: "/employee-transfers" },
  { title: "Detail", href: `/employee-transfers/${props.transfer.id}` },
];
</script>

<template>
  <Head title="Employee Transfer Detail" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center gap-3">
        <Button variant="outline" size="icon" as-child>
          <Link href="/employee-transfers"><ArrowLeft class="h-4 w-4" /></Link>
        </Button>
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Transfer Detail
          </h1>
          <p class="text-zinc-500 mt-1">Status: {{ transfer.status }}</p>
        </div>
      </div>

      <div
        class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 space-y-3"
      >
        <div class="text-sm text-zinc-500">Employee</div>
        <div class="text-base font-medium text-zinc-900 dark:text-white">
          {{ transfer.employee?.employee_code }} -
          {{ transfer.employee?.first_name }} {{ transfer.employee?.last_name }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
          <div>
            <div class="text-sm text-zinc-500">From</div>
            <div class="text-base font-medium text-zinc-900 dark:text-white">
              {{ transfer.from_organization?.name }}
            </div>
          </div>
          <div>
            <div class="text-sm text-zinc-500">To</div>
            <div class="text-base font-medium text-zinc-900 dark:text-white">
              {{ transfer.to_organization?.name }}
            </div>
          </div>
          <div>
            <div class="text-sm text-zinc-500">Effective Date</div>
            <div class="text-base font-medium text-zinc-900 dark:text-white">
              {{ transfer.effective_date }}
            </div>
          </div>
          <div>
            <div class="text-sm text-zinc-500">Requested By</div>
            <div class="text-base font-medium text-zinc-900 dark:text-white">
              {{ transfer.requested_by?.name ?? "-" }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
