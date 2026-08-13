<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  ArrowLeft,
  BookOpen,
  ShieldCheck,
  ChevronLeft,
  ChevronRight,
  ListChecks,
  Printer,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import DocumentationToc from "@/components/Documentation/DocumentationToc.vue";
import MarkdownArticle from "@/components/Documentation/MarkdownArticle.vue";
import ScreenshotViewer from "@/components/Documentation/ScreenshotViewer.vue";
import { Badge } from "@/components/ui/badge";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
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
  auth?: {
    roles?: Array<{ name?: string } | string>;
  };
};

const props = defineProps<InertiaPageProps>();

const page = usePage();

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

// Maintainer-only technical metadata is hidden by default; toggle
// with the "?teknis=1" query parameter so a System Admin reviewer
// can still inspect it without exposing it to other users.
const isSystemAdmin = computed(() => {
  const roles = (
    (
      page.props.auth as
        { roles?: Array<{ name?: string } | string> } | undefined
    )?.roles ?? []
  ).map((role) => (typeof role === "string" ? role : (role.name ?? "")));
  return roles.includes("System Admin");
});

const showTechnicalMetadata = computed(() => {
  if (typeof window === "undefined") {
    return false;
  }
  return (
    isSystemAdmin.value &&
    new URLSearchParams(window.location.search).get("teknis") === "1"
  );
});

const screenshotManifest = ref<{
  entries: Array<Record<string, unknown>>;
} | null>(null);
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
      viewport: String(entry.viewport ?? "desktop") as
        "desktop" | "tablet" | "mobile",
    }));
});

// TOC items come from the MarkdownArticle instance via an explicit
// `toc-ready` event. Listening to `defineExpose` causes an implicit
// dependency on Vue's public-instance ref-unwrapping behaviour,
// which is not stable across Vue versions.
const tocItems = ref<Array<{ level: number; id: string; text: string }>>([]);

function captureToc(
  items: Array<{ level: number; id: string; text: string }>,
): void {
  tocItems.value = items;
}

onMounted(async () => {
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

    <div
      class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6 print:p-0 print:space-y-4"
    >
      <Link
        href="/documentation"
        class="inline-flex items-center gap-1 text-sm text-zinc-500 transition hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-300 print:hidden"
      >
        <ArrowLeft class="h-4 w-4" />
        Kembali ke Pusat Panduan
      </Link>

      <div class="grid gap-6 lg:grid-cols-[1fr_16rem]">
        <div class="space-y-6 print:space-y-4">
          <Card
            class="overflow-hidden border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900 print:border-0 print:shadow-none print:bg-transparent"
          >
            <CardHeader class="space-y-3">
              <div class="flex items-center gap-2">
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
              </div>
              <CardTitle class="text-2xl leading-tight print:text-2xl">
                {{ article.title }}
              </CardTitle>
              <CardDescription class="text-base leading-relaxed">{{
                article.summary
              }}</CardDescription>
              <div
                v-if="showTechnicalMetadata"
                class="rounded-md border border-dashed border-zinc-300 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-300 print:hidden"
              >
                <p class="font-semibold uppercase tracking-wide text-zinc-500">
                  Metadata teknis (khusus System Admin)
                </p>
                <p class="mt-1">
                  Commit tinjauan:
                  <code
                    class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] dark:bg-zinc-800"
                  >
                    {{ article.last_reviewed_commit }}
                  </code>
                </p>
                <p>
                  Status: {{ article.status }} · Modul: {{ article.module }}
                </p>
              </div>
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
            <CardContent
              class="border-t border-zinc-100 pt-6 dark:border-zinc-800"
            >
              <MarkdownArticle
                v-if="article.body"
                :body="article.body"
                :slug="article.slug"
                @toc-ready="captureToc"
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
            data-testid="documentation-related-articles"
            class="border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 print:hidden"
          >
            <CardHeader>
              <CardTitle class="flex items-center gap-2 text-base">
                <ListChecks
                  class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                />
                Prosedur terkait
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="grid gap-2 sm:grid-cols-2">
                <li v-for="related in navigation.related" :key="related.slug">
                  <Link
                    :href="`/documentation/${related.slug}`"
                    class="group flex flex-col gap-0.5 rounded-lg border border-zinc-200 p-3 transition hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-zinc-800 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-950/20"
                  >
                    <span
                      class="text-sm font-medium text-zinc-900 group-hover:text-emerald-700 dark:text-zinc-100 dark:group-hover:text-emerald-300"
                    >
                      {{ related.title }}
                    </span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{
                      related.category
                    }}</span>
                  </Link>
                </li>
              </ul>
            </CardContent>
          </Card>

          <nav
            v-if="navigation.previous || navigation.next"
            aria-label="Navigasi artikel"
            data-testid="documentation-article-navigation"
            class="flex flex-col gap-3 sm:flex-row sm:justify-between print:hidden"
          >
            <Link
              v-if="navigation.previous"
              :href="`/documentation/${navigation.previous.slug}`"
              class="group flex flex-1 items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 transition hover:border-emerald-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-500/30"
            >
              <ChevronLeft
                class="h-5 w-5 shrink-0 text-zinc-400 transition group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
              />
              <span class="flex flex-col text-left">
                <span class="text-[10px] uppercase tracking-wider text-zinc-400"
                  >Sebelumnya</span
                >
                <span
                  class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                  >{{ navigation.previous.title }}</span
                >
              </span>
            </Link>
            <span v-else class="hidden flex-1 sm:block" />
            <Link
              v-if="navigation.next"
              :href="`/documentation/${navigation.next.slug}`"
              class="group flex flex-1 items-center justify-end gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-right transition hover:border-emerald-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-500/30"
            >
              <span class="flex flex-col">
                <span class="text-[10px] uppercase tracking-wider text-zinc-400"
                  >Berikutnya</span
                >
                <span
                  class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                  >{{ navigation.next.title }}</span
                >
              </span>
              <ChevronRight
                class="h-5 w-5 shrink-0 text-zinc-400 transition group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
              />
            </Link>
            <span v-else class="hidden flex-1 sm:block" />
          </nav>
        </div>

        <aside class="space-y-4 print:hidden">
          <DocumentationToc
            v-if="tocItems.length > 0"
            :items="tocItems"
            :article-slug="article.slug"
          />
          <Card
            v-if="showTechnicalMetadata && article.permissions.length > 0"
            class="border-zinc-200/80 bg-white/95 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <CardHeader>
              <CardTitle class="text-sm">Izin yang dibutuhkan</CardTitle>
              <CardDescription>
                Mode pencocokan:
                <code
                  class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800"
                >
                  {{ article.permission_mode }}
                </code>
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ul class="space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                <li v-for="perm in article.permissions" :key="perm">
                  <code
                    class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800"
                  >
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
