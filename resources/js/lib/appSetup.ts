import type { App, DirectiveBinding } from "vue";
import * as routes from "@/routes";

const userCan = (binding: DirectiveBinding, value: unknown): boolean => {
  const permissions = binding.instance?.$page?.props?.auth?.permissions ?? [];
  const required = Array.isArray(value) ? value : [value];

  return required
    .filter(
      (permission): permission is string =>
        typeof permission === "string" && permission.length > 0,
    )
    .some((permission) => permissions.includes(permission));
};

const userCanAll = (binding: DirectiveBinding, value: unknown): boolean => {
  const permissions = binding.instance?.$page?.props?.auth?.permissions ?? [];
  const required = Array.isArray(value) ? value : [value];

  return required
    .filter(
      (permission): permission is string =>
        typeof permission === "string" && permission.length > 0,
    )
    .every((permission) => permissions.includes(permission));
};

const applyCanDirective = (
  el: HTMLElement,
  binding: DirectiveBinding,
): void => {
  el.hidden = !userCan(binding, binding.value);
};

const applyCanAllDirective = (
  el: HTMLElement,
  binding: DirectiveBinding,
): void => {
  el.hidden = !userCanAll(binding, binding.value);
};

export const registerAppSharedFeatures = (vueApp: App): void => {
  (vueApp.config.globalProperties as Record<string, unknown>).route = routes;
  vueApp.directive("can", {
    mounted: applyCanDirective,
    updated: applyCanDirective,
    getSSRProps(binding) {
      return userCan(binding, binding.value) ? {} : { hidden: true };
    },
  });
  vueApp.directive("can-all", {
    mounted: applyCanAllDirective,
    updated: applyCanAllDirective,
    getSSRProps(binding) {
      return userCanAll(binding, binding.value) ? {} : { hidden: true };
    },
  });
};
