import { router } from "@inertiajs/vue3";
import { onBeforeUnmount, ref, watch  } from "vue";
import type {Ref} from "vue";

type Filters = Record<string, string | number | boolean | null | undefined>;

interface TableFilterOptions {
  route?: string;
  debounceMs?: number;
  only?: string[];
}

export function useTableFilters(
  filters: Ref<Filters>,
  options: TableFilterOptions = {},
) {
  const isFiltering = ref(false);
  const debounceMs = options.debounceMs ?? 350;
  let timeoutId: ReturnType<typeof setTimeout> | null = null;

  const cleanFilters = (): Record<string, string | number | boolean> => {
    return Object.fromEntries(
      Object.entries(filters.value).filter(
        ([, value]) => value !== null && value !== undefined && value !== "",
      ),
    ) as Record<string, string | number | boolean>;
  };

  const applyFilters = (): void => {
    isFiltering.value = true;
    router.get(options.route ?? window.location.pathname, cleanFilters(), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: options.only,
      onFinish: () => {
        isFiltering.value = false;
      },
    });
  };

  const resetFilters = (): void => {
    Object.keys(filters.value).forEach((key) => {
      filters.value[key] = "";
    });
    applyFilters();
  };

  watch(
    filters,
    () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }

      timeoutId = setTimeout(applyFilters, debounceMs);
    },
    { deep: true },
  );

  onBeforeUnmount(() => {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
  });

  return {
    isFiltering,
    applyFilters,
    resetFilters,
  };
}
