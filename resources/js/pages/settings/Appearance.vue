<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AppearanceTabs from "@/components/AppearanceTabs.vue";
import Heading from "@/components/Heading.vue";
import { Card, CardContent } from "@/components/ui/card";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import { edit } from "@/routes/appearance";
import type { BreadcrumbItem } from "@/types";

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: "Appearance settings",
    href: edit(),
  },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance Settings</h1>

    <SettingsLayout>
      <div class="space-y-6">
        <Heading
          variant="small"
          title="Appearance settings"
          description="Update your account's appearance settings"
        />
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <CardContent
            class="space-y-4 bg-gradient-to-br from-white to-zinc-50 py-6 dark:from-zinc-900 dark:to-zinc-950"
          >
            <p class="text-sm text-muted-foreground">
              Choose the visual mode that feels most comfortable for your daily workflow.
            </p>
            <AppearanceTabs />
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
