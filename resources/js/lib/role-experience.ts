import type { Component } from "vue";

export type RoleExperienceKey =
  | "system-admin"
  | "admin-pusat"
  | "pengurus"
  | "manajer"
  | "admin-koperasi"
  | "kasir"
  | "pos-operator"
  | "generic";

export type DashboardAction = {
  label: string;
  description: string;
  href: string;
  icon: Component;
  permissions?: string[];
};

export type RoleExperienceDefinition = {
  badge: string;
  title: string;
  description: string;
  actions: DashboardAction[];
};

export type ResolvedRoleExperience = RoleExperienceDefinition & {
  key: RoleExperienceKey;
  ctaLabel: string;
  ctaHref: string;
  actions: DashboardAction[];
};

const ROLE_PRIORITY: Array<{
  role: string;
  key: RoleExperienceKey;
}> = [
  { role: "System Admin", key: "system-admin" },
  { role: "Admin Pusat", key: "admin-pusat" },
  { role: "Pengurus Koperasi", key: "pengurus" },
  { role: "Manajer Koperasi", key: "manajer" },
  { role: "Admin Koperasi", key: "admin-koperasi" },
  { role: "Kasir Koperasi", key: "kasir" },
];

export function hasAnyPermission(
  userPermissions: string[],
  requiredPermissions?: string[],
): boolean {
  if (!requiredPermissions || requiredPermissions.length === 0) {
    return true;
  }

  return requiredPermissions.some((permission) =>
    userPermissions.includes(permission),
  );
}

export function filterPermittedActions<T extends { permissions?: string[] }>(
  actions: T[],
  userPermissions: string[],
): T[] {
  return actions.filter((action) =>
    hasAnyPermission(userPermissions, action.permissions),
  );
}

export function resolvePrimaryRole(roles: string[]): RoleExperienceKey | null {
  return ROLE_PRIORITY.find(({ role }) => roles.includes(role))?.key ?? null;
}

export function isPlatformExperience(key: RoleExperienceKey): boolean {
  return key === "system-admin" || key === "admin-pusat";
}

export function isAdminNavigationExperience(
  key: RoleExperienceKey | null,
): boolean {
  return key === "admin-koperasi";
}

export function roleExperienceNavigationLabel(
  key: RoleExperienceKey | null,
): string {
  return (
    {
      "system-admin": "Platform",
      "admin-pusat": "Platform",
      pengurus: "Ruang kerja Pengurus",
      manajer: "Ruang kerja Manajer",
      "admin-koperasi": "Ruang kerja Admin Koperasi",
      kasir: "Ruang kerja Kasir",
      generic: "Platform",
    }[key ?? "generic"] ?? "Platform"
  );
}

export function resolveRoleExperience(
  roles: string[],
  permissions: string[],
  definitions: Record<RoleExperienceKey, RoleExperienceDefinition>,
  fallbackAction: Pick<DashboardAction, "label" | "href">,
): ResolvedRoleExperience {
  const explicitRole = resolvePrimaryRole(roles);
  const key =
    explicitRole ??
    (permissions.includes("access_cooperative_pos")
      ? "pos-operator"
      : "generic");
  const definition = definitions[key];
  const actions = filterPermittedActions(definition.actions, permissions);
  const cta = actions[0] ?? fallbackAction;

  return {
    ...definition,
    key,
    actions,
    ctaLabel: cta.label,
    ctaHref: cta.href,
  };
}
