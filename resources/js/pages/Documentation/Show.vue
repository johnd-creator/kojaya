<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowLeft,
  BookOpen,
  ShieldCheck,
  ChevronLeft,
  ChevronRight,
  Tag,
  GitCommit,
  ListChecks,
  Printer,
} from "lucide-vue-next";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import MarkdownArticle from "@/components/Documentation/MarkdownArticle.vue";
import DocumentationToc from "@/components/Documentation/DocumentationToc.vue";
import ScreenshotViewer from "@/components/Documentation/ScreenshotViewer.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type NavItem = {
  slug: string;
  title: string;
  category: string;
  summary: string;
};

type ArticleProps = {
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
  body: string;
};

type InertiaPageProps = {
  article: ArticleProps;
  navigation: {
    previous: NavItem | null;
    next: NavItem | null;
    related: NavItem[];
  };
  contextualHelp: Array<{
    route: string;
    slug: string;
    role: string;
    permission?: string;
    screenshot_state: string;
    label: string;
  }>;
  primaryRole: string;
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

const RISK_LABELS: Record<string, string> = {
  low: "Risiko rendah",
  medium: "Risiko sedang",
  high: "Risiko tinggi",
};

const screenshotManifest = ref<{ entries: Array<Record<string, unknown>> } | null>(null);
const screenshotEntries = computed(() => {
  if (!screenshotManifest.value) {
    return [];
  }
  const lookup = new Map(
    screenshotManifest.value.entries.map((entry) => [
      String(entry.id ?? ""),
      entry,
    ]),
  );
  return props.article.screenshot_entries
    .map((id) => lookup.get(id))
    .filter((entry): entry is Record<string, unknown> => Boolean(entry))
    .map((entry) => ({
      id: String(entry.id ?? ""),
      url: `/docs/user-guide/screens/${String(entry.viewport ?? "desktop")}/${String(entry.id)}.png`,
      alt: String(entry.title ?? entry.id ?? ""),
      caption: typeof entry.caption === "string" ? entry.caption : "",
      viewport: (String(entry.viewport ?? "desktop") as "desktop" | "tablet" | "mobile"),
    }));
});

// TOC items come from the MarkdownArticle instance via defineExpose.
// We keep them as a ref so the DocumentationToc component can react
// to changes (e.g. when the article body updates without a remount).
const tocItems = ref<Array<{ level: number; id: string; text: string }>>([]);

const markdownRef = ref<InstanceType<typeof MarkdownArticle> | null>(null);

function captureToc(): void {
  const exposed = markdownRef.value;
  if (!exposed) {
    tocItems.value = [];
    return;
  }
  tocItems.value = (exposed.toc?.value ?? []) as Array<{
    level: number;
    id: string;
    text: string;
  }>;
}

onMounted(async () => {
  // Capture the TOC after the article has rendered. We use a small
  // queueMicrotask delay because the renderer resets its collected
  // TOC at the start of every computed re-evaluation.
  queueMicrotask(captureToc);
  try {
    const res = await fetch("/docs/user-guide/screenshots.json", {
      headers: { Accept: "application/json" },
    });
    if (res.ok) {
      screenshotManifest.value = await res.json();
    }
  } catch {
    screenshotManifest.value = null;
  }
});

function printArticle(): void {
  if (typeof window === "undefined") {
    return;
  }
  window.print();
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="article.title" />

    <div class="mx-auto max-w-6xl space-y-6 p-6 print:p-0 print:space-y-4">
      <Link
        href="/documentation"
        class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 print:hidden"
      >
        <ArrowLeft class="h-4 w-4" />
        Kembali ke Pusat Panduan
      </Link>

      <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <div class="space-y-6 print:space-y-4">
          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80 print:border-0 print:shadow-none print:bg-transparent"
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
                <Badge
                  v-for="role in article.roles"
                  :key="role"
                  variant="outline"
                  class="text-[10px]"
                >
                  <ShieldCheck class="mr-1 h-3.5 w-3.5" />
                  {{ ROLE_LABELS[role] ?? role }}
                </Badge>
                <Badge variant="outline" class="text-[10px]">
                  <Tag class="mr-1 h-3.5 w-3.5" />
                  {{ RISK_LABELS[article.risk_level] ?? article.risk_level }}
                </Badge>
              </div>
              <CardTitle class="text-2xl print:text-2xl">
                {{ article.title }}
              </CardTitle>
              <CardDescription>{{ article.summary }}</CardDescription>
              <p
                class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 print:hidden"
              >
                <GitCommit class="h-3.5 w-3.5" />
                <span>Tinjau commit:</span>
                <code
                  class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] dark:bg-zinc-800"
                >
                  {{ article.last_reviewed_commit }}
                </code>
                <span aria-hidden="true">·</span>
                <span>Status: {{ article.status }}</span>
                <span aria-hidden="true">·</span>
                <span>Modul: {{ article.module }}</span>
              </p>
              <div class="flex flex-wrap items-center gap-2 print:hidden">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                  data-testid="print-article-button"
                  @click="printArticle"
                >
                  <Printer class="h-3.5 w-3.5" />
                  Cetak Panduan
                </button>
              </div>
            </CardHeader>
            <CardContent>
              <MarkdownArticle
                v-if="article.body"
                ref="markdownRef"
                :body="article.body"
                :slug="article.slug"
                @vue:updated="captureToc"
              />
              <p
                v-else
                class="rounded border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
              >
                Konten artikel tidak tersedia.
              </p>
            </CardContent>
          </Card>

          <ScreenshotViewer :entries="screenshotEntries" />

          <Card
            v-if="navigation.related.length > 0"
            class="border-zinc-200/80 bg-white/95 dark:border-zinc-800/80 dark:bg-zinc-900/80 print:hidden"
          >
            <CardHeader>
              <CardTitle class="flex items-center gap-2 text-base">
                <ListChecks class="h-4 w-4" />
                Prosedur terkait
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2 text-sm">
                <li
                  v-for="related in navigation.related"
                  :key="related.slug"
                  class="flex items-center justify-between gap-2"
                >
                  <Link
                    :href="`/documentation/${related.slug}`"
                    class="text-emerald-700 hover:underline dark:text-emerald-300"
                  >
                    {{ related.title }}
                  </Link>
                  <span class="text-xs text-zinc-500">{{ related.category }}</span>
                </li>
              </ul>
            </CardContent>
          </Card>

          <nav
            v-if="navigation.previous || navigation.next"
            aria-label="Navigasi artikel"
            class="flex flex-col gap-2 sm:flex-row sm:justify-between print:hidden"
          >
            <Link
              v-if="navigation.previous"
              :href="`/documentation/${navigation.previous.slug}`"
              class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
            >
              <ChevronLeft class="h-4 w-4" />
              <span class="flex flex-col text-left">
                <span class="text-[10px] uppercase tracking-wider">Sebelumnya</span>
                <span class="font-medium">{{ navigation.previous.title }}</span>
                <span class="text-[10px] text-zinc-500">{{ navigation.previous.category }}</span>
              </span>
            </Link>
            <span v-else />
            <Link
              v-if="navigation.next"
              :href="`/documentation/${navigation.next.slug}`"
              class="inline-flex items-center justify-end gap-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
            >
              <span class="flex flex-col text-right">
                <span class="text-[10px] uppercase tracking-wider">Berikutnya</span>
                <span class="font-medium">{{ navigation.next.title }}</span>
                <span class="text-[10px] text-zinc-500">{{ navigation.next.category }}</span>
              </span>
              <ChevronRight class="h-4 w-4" />
            </Link>
            <span v-else />
          </nav>
        </div>

        <aside class="space-y-4 print:hidden">
          <DocumentationToc
            v-if="tocItems.length > 0"
            :items="tocItems"
            :article-slug="article.slug"
          />
          <Card
            v-if="article.permissions.length > 0"
            class="border-zinc-200/80 bg-white/95 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <CardHeader>
              <CardTitle class="text-sm">Izin yang dibutuhkan</CardTitle>
              <CardDescription>
                Mode pencocokan:
                <code class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800">
                  {{ article.permission_mode }}
                </code>
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ul class="space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                <li v-for="perm in article.permissions" :key="perm">
                  <code class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800">
                    {{ perm }}
                  </code>
                </li>
              </ul>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
