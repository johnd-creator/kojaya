<script setup lang="ts">
import { ref } from "vue";
import { ZoomIn, X } from "lucide-vue-next";

type ScreenshotEntry = {
  id: string;
  url: string;
  alt: string;
  caption?: string;
  viewport: "desktop" | "tablet" | "mobile";
};

const props = defineProps<{
  entries: ScreenshotEntry[];
}>();

const active = ref<ScreenshotEntry | null>(null);

function open(entry: ScreenshotEntry): void {
  active.value = entry;
}

function close(): void {
  active.value = null;
}
</script>

<template>
  <section
    v-if="entries.length > 0"
    aria-label="Screenshot"
    class="space-y-3"
  >
    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
      Tangkapan layar
    </h2>
    <div class="grid gap-4 md:grid-cols-2">
      <figure
        v-for="entry in entries"
        :key="entry.id"
        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
      >
        <button
          type="button"
          class="group relative block w-full focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
          :aria-label="`Perbesar screenshot ${entry.alt}`"
          @click="open(entry)"
        >
          <img
            :src="entry.url"
            :alt="entry.alt"
            loading="lazy"
            class="block h-auto w-full transition group-hover:scale-[1.01]"
          />
          <span
            class="pointer-events-none absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-zinc-900/80 px-2 py-1 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100"
          >
            <ZoomIn class="h-3.5 w-3.5" />
            Zoom
          </span>
          <span
            class="pointer-events-none absolute left-3 top-3 inline-flex items-center rounded-full bg-zinc-900/70 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-white"
          >
            {{ entry.viewport }}
          </span>
        </button>
        <figcaption
          v-if="entry.caption"
          class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400"
        >
          {{ entry.caption }}
        </figcaption>
      </figure>
    </div>

    <div
      v-if="active"
      class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <button
        type="button"
        class="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-zinc-900 shadow"
        @click="close"
      >
        <X class="h-3.5 w-3.5" />
        Tutup
      </button>
      <img
        :src="active.url"
        :alt="active.alt"
        class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl"
      />
    </div>
  </section>
</template>
