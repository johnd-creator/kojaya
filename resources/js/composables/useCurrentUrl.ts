import type { InertiaLinkProps } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import type { ComputedRef, DeepReadonly } from "vue";
import { computed, readonly } from "vue";
import { toUrl } from "@/lib/utils";

export type UseCurrentUrlReturn = {
  currentUrl: DeepReadonly<ComputedRef<string>>;
  isCurrentUrl: (
    urlToCheck: NonNullable<InertiaLinkProps["href"]>,
    currentUrl?: string,
  ) => boolean;
  whenCurrentUrl: <T, F = null>(
    urlToCheck: NonNullable<InertiaLinkProps["href"]>,
    ifTrue: T,
    ifFalse?: F,
  ) => T | F;
};

const page = usePage();
const currentUrlReactive = computed(
  () => new URL(page.url, window?.location.origin).pathname,
);

function pathnameOf(url: string): string {
  try {
    return new URL(url, window.location.origin).pathname;
  } catch {
    return url.split("?")[0].split("#")[0];
  }
}

export function useCurrentUrl(): UseCurrentUrlReturn {
  function isCurrentUrl(
    urlToCheck: NonNullable<InertiaLinkProps["href"]>,
    currentUrl?: string,
  ) {
    const urlToCompare = currentUrl ?? currentUrlReactive.value;
    const urlString = toUrl(urlToCheck) ?? "";

    if (!urlString || urlString.startsWith("#")) {
      return false;
    }

    return pathnameOf(urlString) === urlToCompare;
  }

  function whenCurrentUrl(
    urlToCheck: NonNullable<InertiaLinkProps["href"]>,
    ifTrue: any,
    ifFalse: any = null,
  ) {
    return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
  }

  return {
    currentUrl: readonly(currentUrlReactive),
    isCurrentUrl,
    whenCurrentUrl,
  };
}
