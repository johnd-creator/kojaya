<script setup lang="ts">
import { computed, ref } from "vue";
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
import DocumentationSearch from "@/components/Documentation/DocumentationSearch.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type ArticleSummary = {
  slug: string;
  title: string;
  summary: string;
  category: string;
  module: string;
  roles: string[];
  permissions: string[];
  permission_mode: string;
  risk_level: string;
  screenshot_entries: string[];
  related_articles: string[];
  last_reviewed_commit: string;
  status: string;
  sort_order: number;
  search_text?: string;
};

type InertiaPageProps = {
  sections: Array<{
    category: string;
    articles: ArticleSummary[];
  }>;
  modules: string[];
  articles: ArticleSummary[];
  userRoles: string[];
  primaryRole: string;
  searchEnabled: boolean;
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

const RISK_LABELS: Record<string, string> = {
  low: "Risiko rendah",
  medium: "Risiko sedang",
  high: "Risiko tinggi",
};

const searchQuery = ref("");
const moduleFilter = ref("");

const filteredSlugs = computed<Set<string>>(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (q === "" && moduleFilter.value === "") {
    return new Set(props.articles.map((a) => a.slug));
  }
  return new Set(
    props.articles
      .filter((a) => {
        if (moduleFilter.value !== "" && a.module !== moduleFilter.value) {
          return false;
        }
        if (q === "") {
          return true;
        }
        // Match against title, summary, category, AND the
        // pre-computed full-text search index (body content).
        // The backend strips headings, code fences, link URLs, and
        // markup so the user only sees procedural matches.
        const haystacks = [a.title, a.summary, a.category, a.search_text ?? ""];
        return haystacks.some((haystack) => haystack.toLowerCase().includes(q));
      })
      .map((a) => a.slug),
  );
});

const visibleSections = computed(() => {
  return props.sections
    .map((section) => ({
      category: section.category,
      articles: section.articles.filter((a) => filteredSlugs.value.has(a.slug)),
    }))
    .filter((section) => section.articles.length > 0);
});

const hasResults = computed(() => visibleSections.value.length > 0);
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
            <p
              class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
            >
              Daftar artikel di bawah ini otomatis disaring berdasarkan peran
              dan izin login Anda. Anda hanya melihat prosedur yang relevan
              dengan pekerjaan harian Anda, baik sebagai anggota maupun pengurus
              koperasi.
            </p>
          </div>

          <div
            class="flex flex-wrap items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400"
          >
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

          <DocumentationSearch
            v-if="props.searchEnabled"
            v-model:modelValue="searchQuery"
            v-model:moduleFilter="moduleFilter"
            :articles="props.articles"
            :modules="props.modules"
          />
        </CardContent>
      </Card>

      <div
        v-for="section in visibleSections"
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
            :key="article.slug"
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
                  {{ ROLE_LABELS[article.roles[0] ?? ""] ?? article.roles[0] }}
                </Badge>
              </div>
              <CardDescription>{{ article.summary }}</CardDescription>
            </CardHeader>
            <CardContent class="flex items-center justify-between gap-2">
              <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ RISK_LABELS[article.risk_level] ?? article.risk_level }}
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
        v-if="!hasResults"
        class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400"
      >
        <p class="font-medium">Tidak ada panduan yang cocok.</p>
        <p class="mt-1 text-xs">
          Coba gunakan kata lain atau pilih modul lain. Jika Anda merasa panduan
          yang seharusnya ada belum muncul, hubungi Admin Koperasi.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
