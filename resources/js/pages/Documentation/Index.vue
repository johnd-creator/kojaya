<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowRight,
  ArrowUpRight,
  BookOpen,
  RotateCcw,
  Search,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import DocumentationSearch from "@/components/Documentation/DocumentationSearch.vue";
import PageContainer from "@/components/PageContainer.vue";
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
  all: "Semua",
  admin_pusat: "Admin Pusat",
  admin_koperasi: "Admin Koperasi",
  anggota: "Anggota",
  generic: "Panduan Umum",
  manajer_koperasi: "Manajer Koperasi",
  pengurus_koperasi: "Pengurus Koperasi",
  system_admin: "Semua Panduan",
};

const ROLE_ORDER = [
  "anggota",
  "admin_koperasi",
  "manajer_koperasi",
  "pengurus_koperasi",
];

const CATEGORY_ORDER = [
  "Memulai",
  "Keanggotaan",
  "Simpanan & Pembayaran",
  "Pinjaman",
  "POS & Inventori",
  "Keuangan",
  "SHU & Tata Kelola",
];

const CATEGORY_SUFFIX_MAP: Record<string, string> = {
  Glosarium: "Referensi",
  Inventori: "POS & Inventori",
  Iuran: "Simpanan & Pembayaran",
  Keanggotaan: "Keanggotaan",
  Keuangan: "Keuangan",
  Laporan: "Keuangan",
  Memulai: "Memulai",
  Operasional: "POS & Inventori",
  Pembayaran: "Simpanan & Pembayaran",
  Pinjaman: "Pinjaman",
  POS: "POS & Inventori",
  SHU: "SHU & Tata Kelola",
  Simpanan: "Simpanan & Pembayaran",
  "Tata Kelola": "SHU & Tata Kelola",
};

const QUICK_START_SLUGS: Record<string, string[]> = {
  anggota: [
    "anggota-portal-overview",
    "anggota-payment-flow",
    "anggota-loan-flow",
  ],
  admin_koperasi: [
    "admin-koperasi-operational-dashboard",
    "admin-koperasi-payment-queue",
    "admin-koperasi-loan-types",
    "admin-koperasi-pos-inventory",
  ],
  manajer_koperasi: ["manajer-loan-review", "manajer-financial-monitoring"],
  pengurus_koperasi: ["pengurus-loan-approval", "pengurus-shu-and-governance"],
};

function normalizeCategory(article: ArticleSummary): string {
  const suffix = article.category.includes(" · ")
    ? article.category.split(" · ").slice(1).join(" · ")
    : article.category;
  return CATEGORY_SUFFIX_MAP[suffix] ?? suffix;
}

function isReferenceArticle(article: ArticleSummary): boolean {
  return (
    article.roles.includes("all") ||
    article.roles.includes("shared") ||
    article.slug === "shared-glossary" ||
    normalizeCategory(article) === "Referensi"
  );
}

function articleWorkflowRoles(article: ArticleSummary): string[] {
  return article.roles.filter((r) => r !== "all" && r !== "shared");
}

function categorySlug(cat: string): string {
  return cat
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

const referenceArticles = computed(() =>
  [...props.articles]
    .filter((a) => isReferenceArticle(a))
    .sort((a, b) => a.sort_order - b.sort_order),
);

const workflowArticlesAll = computed(() =>
  [...props.articles]
    .filter((a) => !isReferenceArticle(a))
    .sort((a, b) => a.sort_order - b.sort_order),
);

/**
 * The context selector is only available to System Admin, who is
 * backend-authorized to see all published articles. For every other
 * role the backend returns a single-role payload, so we show a static
 * label instead of deriving misleading options from cross-role article
 * metadata.
 */
const isSystemAdmin = computed(() => props.primaryRole === "system_admin");

const availableRoles = computed<string[]>(() => {
  if (!isSystemAdmin.value) {
    return ROLE_ORDER.filter((r) => r === props.primaryRole);
  }
  const roles = new Set<string>();
  workflowArticlesAll.value.forEach((a) =>
    articleWorkflowRoles(a).forEach((r) => roles.add(r)),
  );
  return ROLE_ORDER.filter((r) => roles.has(r));
});

const isMultiRole = computed(() => isSystemAdmin.value);

const effectiveDefaultRole = computed(() =>
  isSystemAdmin.value ? "all" : props.primaryRole,
);

const selectedRole = ref(effectiveDefaultRole.value);
const selectedCategory = ref("Semua");
const searchQuery = ref("");

watch(selectedRole, () => {
  selectedCategory.value = "Semua";
});

const roleFilteredArticles = computed(() => {
  if (selectedRole.value === "all") {
    return workflowArticlesAll.value;
  }
  return workflowArticlesAll.value.filter((a) =>
    articleWorkflowRoles(a).includes(selectedRole.value),
  );
});

const availableCategories = computed<string[]>(() => {
  const present = new Set(
    roleFilteredArticles.value.map((a) => normalizeCategory(a)),
  );
  const ordered = CATEGORY_ORDER.filter((c) => present.has(c));
  present.forEach((c) => {
    if (!ordered.includes(c) && c !== "Referensi") ordered.push(c);
  });
  return ordered;
});

const categoryOptions = computed(() => {
  const options = [
    {
      value: "Semua",
      label: "Semua",
      count: roleFilteredArticles.value.length,
    },
  ];
  availableCategories.value.forEach((cat) => {
    options.push({
      value: cat,
      label: cat,
      count: roleFilteredArticles.value.filter(
        (a) => normalizeCategory(a) === cat,
      ).length,
    });
  });
  return options;
});

const displayArticles = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  let result = roleFilteredArticles.value;
  if (selectedCategory.value !== "Semua") {
    result = result.filter(
      (a) => normalizeCategory(a) === selectedCategory.value,
    );
  }
  if (q !== "") {
    result = result.filter((a) =>
      [a.title, a.summary, a.category, a.search_text ?? ""].some((h) =>
        h.toLowerCase().includes(q),
      ),
    );
  }
  return result;
});

const quickStartArticles = computed(() => {
  // System Admin in "Semua" mode should not see random Quick Start
  // cards. An orientation block is shown instead (see template).
  if (isSystemAdmin.value && selectedRole.value === "all") {
    return [];
  }

  const role =
    selectedRole.value !== "all"
      ? selectedRole.value
      : effectiveDefaultRole.value !== "all"
        ? effectiveDefaultRole.value
        : null;

  const articlesBySlug = new Map(props.articles.map((a) => [a.slug, a]));

  if (role && QUICK_START_SLUGS[role]) {
    return QUICK_START_SLUGS[role]
      .map((slug) => articlesBySlug.get(slug))
      .filter((a): a is ArticleSummary => Boolean(a))
      .slice(0, 4);
  }

  return [];
});

const showQuickStartOrientation = computed(
  () => isSystemAdmin.value && selectedRole.value === "all",
);

const singleRoleLabel = computed(
  () =>
    ROLE_LABELS[props.primaryRole] ??
    ROLE_LABELS[availableRoles.value[0] ?? ""] ??
    "Panduan untuk Anda",
);

const hasActiveSearchOrCategory = computed(
  () => searchQuery.value.trim() !== "" || selectedCategory.value !== "Semua",
);

function roleBadgeLabel(article: ArticleSummary): string {
  return articleWorkflowRoles(article)
    .map((r) => ROLE_LABELS[r] ?? r)
    .join(", ");
}

function resetFilters(): void {
  searchQuery.value = "";
  selectedCategory.value = "Semua";
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Pusat Panduan" />

    <PageContainer variant="list" class="max-w-7xl">
      <!-- HERO -->
      <section data-testid="documentation-hero" class="space-y-5">
        <div class="space-y-2">
          <div
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300"
          >
            <BookOpen class="h-4 w-4" />
            <span>Pusat Panduan</span>
          </div>
          <h1
            class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50 sm:text-3xl"
          >
            Apa yang ingin Anda lakukan?
          </h1>
          <p
            class="max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
          >
            Cari panduan penggunaan Kojaya berdasarkan aktivitas yang ingin Anda
            kerjakan.
          </p>
        </div>

        <div
          v-if="props.searchEnabled"
          class="flex flex-col gap-3 lg:flex-row lg:items-center"
        >
          <div class="flex-1">
            <DocumentationSearch v-model:modelValue="searchQuery" />
          </div>

          <div v-if="isMultiRole" class="flex items-center gap-2">
            <label
              for="documentation-role-filter"
              class="shrink-0 whitespace-nowrap text-xs font-medium text-zinc-600 dark:text-zinc-400"
            >
              Lihat panduan sebagai
            </label>
            <select
              id="documentation-role-filter"
              v-model="selectedRole"
              data-testid="documentation-role-filter"
              aria-label="Lihat panduan sebagai"
              class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-3 pr-8 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 lg:w-48"
            >
              <option value="all">Semua</option>
              <option v-for="role in availableRoles" :key="role" :value="role">
                {{ ROLE_LABELS[role] ?? role }}
              </option>
            </select>
          </div>

          <div
            v-else
            data-testid="documentation-role-summary"
            class="flex items-center gap-2 text-sm"
          >
            <span class="text-zinc-500 dark:text-zinc-400">Panduan untuk:</span>
            <span class="font-semibold text-zinc-900 dark:text-zinc-100">
              {{ singleRoleLabel }}
            </span>
          </div>
        </div>
      </section>

      <!-- QUICK START -->
      <section
        v-if="showQuickStartOrientation"
        data-testid="documentation-quick-start"
        class="space-y-3"
      >
        <div>
          <h2
            class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
          >
            Mulai dari sini
          </h2>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Pilih peran di atas untuk melihat panduan yang direkomendasikan.
          </p>
        </div>
      </section>

      <section
        v-else-if="quickStartArticles.length > 0"
        data-testid="documentation-quick-start"
        class="space-y-4"
      >
        <div>
          <h2
            class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
          >
            Mulai dari sini
          </h2>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Panduan yang paling membantu untuk memulai pekerjaan Anda.
          </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Link
            v-for="(article, index) in quickStartArticles"
            :key="article.slug"
            :href="`/documentation/${article.slug}`"
            data-testid="documentation-quick-start-item"
            class="group flex flex-col gap-3 rounded-xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/80 to-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:border-emerald-500/20 dark:from-emerald-950/30 dark:to-zinc-900 dark:hover:border-emerald-500/40"
          >
            <div class="flex items-center justify-between">
              <span
                class="text-2xl font-bold tabular-nums text-emerald-300 dark:text-emerald-700"
              >
                {{ String(index + 1).padStart(2, "0") }}
              </span>
              <ArrowRight
                class="h-4 w-4 text-emerald-600 transition-transform group-hover:translate-x-0.5 dark:text-emerald-400"
              />
            </div>
            <div class="space-y-1">
              <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ article.title }}
              </p>
              <p
                class="line-clamp-2 text-xs leading-5 text-zinc-600 dark:text-zinc-400"
              >
                {{ article.summary }}
              </p>
            </div>
          </Link>
        </div>
      </section>

      <!-- ALL GUIDES -->
      <section data-testid="documentation-article-sections" class="space-y-4">
        <div class="flex items-end justify-between gap-4">
          <div>
            <h2
              class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
            >
              Semua Panduan
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
              Temukan panduan lain sesuai pekerjaan Anda.
            </p>
          </div>
          <span
            class="hidden shrink-0 text-xs text-zinc-500 dark:text-zinc-400 sm:inline"
          >
            {{ displayArticles.length }} panduan
          </span>
        </div>

        <!-- Category chips -->
        <div
          data-testid="documentation-category-filter"
          class="flex flex-wrap gap-2"
          role="group"
          aria-label="Filter kategori panduan"
        >
          <button
            v-for="option in categoryOptions"
            :key="option.value"
            type="button"
            :aria-pressed="selectedCategory === option.value"
            :data-testid="`documentation-category-${categorySlug(option.value)}`"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
            :class="
              selectedCategory === option.value
                ? 'border-emerald-500 bg-emerald-500 text-white'
                : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
            "
            @click="selectedCategory = option.value"
          >
            {{ option.label }}
            <span
              :class="
                selectedCategory === option.value
                  ? 'text-emerald-100'
                  : 'text-zinc-400 dark:text-zinc-500'
              "
            >
              {{ option.count }}
            </span>
          </button>
        </div>

        <!-- Unified guide grid -->
        <div
          v-if="displayArticles.length > 0"
          data-testid="documentation-guide-grid"
          class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
          <article
            v-for="article in displayArticles"
            :key="article.slug"
            data-testid="documentation-article-card"
            class="group relative flex flex-col rounded-xl border border-zinc-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
          >
            <div class="flex flex-1 flex-col gap-2">
              <div class="flex items-center gap-2">
                <span
                  class="text-xs font-medium text-emerald-700 dark:text-emerald-300"
                >
                  {{ normalizeCategory(article) }}
                </span>
                <span
                  v-if="selectedRole === 'all' && isMultiRole"
                  class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                >
                  {{ roleBadgeLabel(article) }}
                </span>
              </div>
              <h3
                class="text-sm font-semibold leading-6 text-zinc-900 dark:text-zinc-100"
              >
                {{ article.title }}
              </h3>
              <p
                class="line-clamp-2 text-xs leading-5 text-zinc-600 dark:text-zinc-400"
              >
                {{ article.summary }}
              </p>
            </div>
            <Link
              :href="`/documentation/${article.slug}`"
              data-testid="documentation-article-cta"
              class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 after:absolute after:inset-0 after:content-[''] hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:text-emerald-300 dark:hover:text-emerald-200"
              :aria-label="`Buka panduan: ${article.title}`"
            >
              Buka panduan
              <ArrowRight class="h-4 w-4" />
            </Link>
          </article>
        </div>

        <!-- Empty state -->
        <div
          v-else
          data-testid="documentation-empty-state"
          class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
        >
          <Search class="mx-auto h-5 w-5 text-zinc-400" />
          <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            Tidak menemukan panduan
          </p>
          <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Coba gunakan kata lain atau pilih kategori lain.
          </p>
          <button
            v-if="hasActiveSearchOrCategory"
            type="button"
            data-testid="documentation-reset-filters"
            class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
            @click="resetFilters"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Reset filter
          </button>
        </div>
      </section>

      <!-- REFERENCES -->
      <section
        v-if="referenceArticles.length > 0"
        data-testid="documentation-reference-section"
        class="space-y-3 border-t border-zinc-200 pt-6 dark:border-zinc-800"
      >
        <h2
          class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
        >
          Referensi
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          <Link
            v-for="article in referenceArticles"
            :key="article.slug"
            :href="`/documentation/${article.slug}`"
            class="group flex items-center gap-4 rounded-xl border border-zinc-200/80 bg-white p-5 transition hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
            >
              <BookOpen class="h-5 w-5" />
            </div>
            <div class="flex-1 space-y-1">
              <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ article.title }}
              </p>
              <p class="line-clamp-1 text-xs text-zinc-600 dark:text-zinc-400">
                {{ article.summary }}
              </p>
            </div>
            <ArrowUpRight
              class="h-4 w-4 shrink-0 text-zinc-400 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
            />
          </Link>
        </div>
      </section>
    </PageContainer>
  </AppLayout>
</template>
