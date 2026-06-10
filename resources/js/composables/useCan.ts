import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

export function useCan() {
  const page = usePage();

  const userPermissions = computed<string[]>(() => {
    const permissions = page.props.auth?.permissions as string[] | undefined;
    return permissions ?? [];
  });

  const userRoles = computed<string[]>(() => {
    const auth = page.props.auth as any;
    return (auth?.roles ?? []).map((r: any) => (typeof r === "string" ? r : r.name ?? ""));
  });

  const isSystemAdmin = computed(() =>
    userRoles.value.includes("System Admin") || userRoles.value.includes("Admin Pusat")
  );

  function can(permissions: string | string[]): boolean {
    if (isSystemAdmin.value) return true;

    const required = Array.isArray(permissions) ? permissions : [permissions];
    return required.some((p) => userPermissions.value.includes(p));
  }

  function canAll(permissions: string[]): boolean {
    if (isSystemAdmin.value) return true;
    return permissions.every((p) => userPermissions.value.includes(p));
  }

  return { can, canAll, userPermissions, userRoles, isSystemAdmin };
}
