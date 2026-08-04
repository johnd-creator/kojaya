<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, BookOpen, ShieldCheck } from "lucide-vue-next";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type InertiaPageProps = {
  article: {
    id: number;
    slug: string;
    title: string;
    summary: string;
    category: string;
    target_role: string;
    body_markdown: string;
  };
};

const props = defineProps<InertiaPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Pusat Panduan", href: "/documentation" },
  { title: props.article.title, href: `/documentation/${props.article.slug}` },
];

const ROLE_LABELS: Record<string, string> = {
  all: "Semua peran",
  anggota: "Anggota",
  admin_koperasi: "Admin Koperasi",
  manajer_koperasi: "Manajer Koperasi",
  pengurus_koperasi: "Pengurus Koperasi",
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="article.title" />

    <div class="mx-auto max-w-3xl space-y-6 p-6">
      <Link
        href="/documentation"
        class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
      >
        <ArrowLeft class="h-4 w-4" />
        Kembali ke Pusat Panduan
      </Link>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <CardHeader class="space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <Badge
              variant="secondary"
              class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
              <BookOpen class="mr-1 h-3.5 w-3.5" />
              {{ article.category }}
            </Badge>
            <Badge variant="outline" class="text-[10px]">
              <ShieldCheck class="mr-1 h-3.5 w-3.5" />
              {{ ROLE_LABELS[article.target_role] ?? article.target_role }}
            </Badge>
          </div>
          <CardTitle class="text-2xl">{{ article.title }}</CardTitle>
          <CardDescription>{{ article.summary }}</CardDescription>
        </CardHeader>
        <CardContent>
          <article
            class="prose prose-zinc max-w-none whitespace-pre-line text-sm leading-7 text-zinc-700 dark:prose-invert dark:text-zinc-200"
          >
            {{ article.body_markdown }}
          </article>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
