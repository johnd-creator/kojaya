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

export type ServerPrimaryRole = Exclude<RoleExperienceKey, "pos-operator">;

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

/**
 * Resolves the workspace from the backend authority shared by Inertia.
 *
 * Role arrays remain a compatibility fallback only when an older response does
 * not include `auth.primary_role`. A shared generic/null role can still expose
 * the permission-derived POS workspace.
 */
export function resolveEffectiveExperience(
  serverPrimaryRole: ServerPrimaryRole | null | undefined,
  roles: string[],
  permissions: string[],
): RoleExperienceKey {
  if (serverPrimaryRole && serverPrimaryRole !== "generic") {
    return serverPrimaryRole;
  }

  if (serverPrimaryRole === undefined) {
    return (
      resolvePrimaryRole(roles) ??
      (permissions.includes("access_cooperative_pos")
        ? "pos-operator"
        : "generic")
    );
  }

  return permissions.includes("access_cooperative_pos")
    ? "pos-operator"
    : "generic";
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
      "pos-operator": "Ruang kerja Operator POS",
      generic: "Ruang kerja",
    }[key ?? "generic"] ?? "Platform"
  );
}

export function resolveRoleExperience(
  key: RoleExperienceKey,
  permissions: string[],
  definitions: Record<RoleExperienceKey, RoleExperienceDefinition>,
  fallbackAction: Pick<DashboardAction, "label" | "href">,
): ResolvedRoleExperience {
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
