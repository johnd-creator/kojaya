<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
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
const triggerBeforeOpen = ref<HTMLElement | null>(null);
const dialogRef = ref<HTMLDivElement | null>(null);
const closeBtnRef = ref<HTMLButtonElement | null>(null);

let previousBodyOverflow: string | null = null;

function lockBackgroundScroll(): void {
  if (typeof document === "undefined") {
    return;
  }
  previousBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = "hidden";
}

function unlockBackgroundScroll(): void {
  if (typeof document === "undefined") {
    return;
  }
  document.body.style.overflow = previousBodyOverflow ?? "";
  previousBodyOverflow = null;
}

function open(entry: ScreenshotEntry, event: MouseEvent): void {
  triggerBeforeOpen.value =
    event.currentTarget instanceof HTMLElement ? event.currentTarget : null;
  active.value = entry;
  lockBackgroundScroll();
  void nextTick(() => {
    closeBtnRef.value?.focus();
  });
}

function close(): void {
  active.value = null;
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape") {
    if (active.value === null) {
      return;
    }
    event.preventDefault();
    close();
    return;
  }
  if (event.key === "Tab") {
    const focusable = getFocusableElements();
    if (focusable.length === 0) {
      return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const activeEl = document.activeElement;
    if (event.shiftKey) {
      if (activeEl === first || !dialogRef.value?.contains(activeEl)) {
        event.preventDefault();
        last.focus();
      }
    } else {
      if (activeEl === last || !dialogRef.value?.contains(activeEl)) {
        event.preventDefault();
        first.focus();
      }
    }
  }
}

function getFocusableElements(): HTMLElement[] {
  if (!dialogRef.value) {
    return [];
  }
  const selector =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
  return Array.from(
    dialogRef.value.querySelectorAll<HTMLElement>(selector),
  ).filter((el) => el.offsetParent !== null || el === document.activeElement);
}

function onBackdropClick(event: MouseEvent): void {
  if (event.target !== event.currentTarget) {
    return;
  }
  close();
}

watch(active, (value) => {
  if (typeof document === "undefined") {
    return;
  }
  if (value === null) {
    unlockBackgroundScroll();
    document.removeEventListener("keydown", onKeydown);
    triggerBeforeOpen.value?.focus();
    triggerBeforeOpen.value = null;
    return;
  }
  document.addEventListener("keydown", onKeydown);
});

onBeforeUnmount(() => {
  if (typeof document === "undefined") {
    return;
  }
  document.removeEventListener("keydown", onKeydown);
  unlockBackgroundScroll();
});
</script>

<template>
  <section v-if="entries.length > 0" aria-label="Screenshot" class="space-y-3">
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
          :data-testid="`documentation-screenshot-${entry.id}`"
          :aria-label="`Perbesar screenshot ${entry.alt}`"
          @click="open(entry, $event)"
        >
          <img
            :src="entry.url"
            :alt="entry.alt"
            loading="eager"
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
      ref="dialogRef"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-zinc-950/80 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="screenshot-zoom-title"
      @click="onBackdropClick"
    >
      <h2 id="screenshot-zoom-title" class="sr-only">
        Tangkapan layar: {{ active.alt }}
      </h2>
      <button
        ref="closeBtnRef"
        type="button"
        class="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-zinc-900 shadow"
        aria-label="Tutup pratinjau tangkapan layar"
        @click="close"
      >
        <X class="h-3.5 w-3.5" />
        Tutup
      </button>
      <img
        :src="active.url"
        :alt="active.alt"
        class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl"
        @click.stop
      />
    </div>
  </section>
</template>
