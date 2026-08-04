<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { BookOpen, ArrowRight, ShieldCheck, ListChecks } from "lucide-vue-next";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type InertiaPageProps = {
  sections: Array<{
    category: string;
    articles: Array<{
      id: number;
      slug: string;
      title: string;
      summary: string;
      category: string;
      target_role: string;
      required_permissions: string[];
    }>;
  }>;
  userRoles: string[];
};

const props = defineProps<InertiaPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Pusat Panduan", href: "/documentation" },
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
    <Head title="Pusat Panduan" />

    <div class="space-y-8 p-6">
      <Card
        class="overflow-hidden border-zinc-200/80 bg-gradient-to-br from-white via-emerald-50/80 to-zinc-100 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-zinc-950 dark:via-emerald-950/20 dark:to-zinc-900"
      >
        <CardContent class="space-y-5 p-6 sm:p-8">
          <div class="flex flex-wrap gap-2">
            <Badge
              variant="secondary"
              class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
              <BookOpen class="mr-1 h-3.5 w-3.5" />
              Pusat Panduan
            </Badge>
            <Badge variant="outline">
              <ShieldCheck class="mr-1 h-3.5 w-3.5" />
              Disaring per peran
            </Badge>
          </div>

          <div class="space-y-2">
            <h1
              class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
            >
              Panduan penggunaan Kojaya untuk peran Anda.
            </h1>
            <p class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
              Daftar artikel di bawah ini otomatis disaring berdasarkan peran
              dan izin login Anda. Anda hanya melihat prosedur yang relevan
              dengan pekerjaan harian Anda, baik sebagai anggota maupun
              pengurus koperasi.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
            <ListChecks class="h-4 w-4" />
            <span>Peran Anda:&nbsp;</span>
            <span
              v-for="role in props.userRoles"
              :key="role"
              class="inline-flex items-center rounded-full border border-zinc-200 bg-white/80 px-2 py-0.5 font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-200"
            >
              {{ role }}
            </span>
          </div>
        </CardContent>
      </Card>

      <div
        v-for="section in props.sections"
        :key="section.category"
        class="space-y-3"
      >
        <div class="flex items-baseline justify-between">
          <h2
            class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
          >
            {{ section.category }}
          </h2>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ section.articles.length }} artikel
          </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <Card
            v-for="article in section.articles"
            :key="article.id"
            class="border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <CardHeader>
              <div class="flex items-center justify-between gap-2">
                <CardTitle class="text-base">
                  <Link
                    :href="`/documentation/${article.slug}`"
                    class="hover:underline"
                  >
                    {{ article.title }}
                  </Link>
                </CardTitle>
                <Badge variant="outline" class="shrink-0 text-[10px]">
                  {{ ROLE_LABELS[article.target_role] ?? article.target_role }}
                </Badge>
              </div>
              <CardDescription>{{ article.summary }}</CardDescription>
            </CardHeader>
            <CardContent class="flex items-center justify-between">
              <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ article.required_permissions.length }} izin spesifik
              </span>
              <Link
                :href="`/documentation/${article.slug}`"
                class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-300"
              >
                Baca
                <ArrowRight class="h-4 w-4" />
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>

      <div
        v-if="props.sections.length === 0"
        class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400"
      >
        Belum ada artikel yang dipublikasikan untuk peran Anda.
      </div>
    </div>
  </AppLayout>
</template>
