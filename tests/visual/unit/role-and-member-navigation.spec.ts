import { expect, test } from "@playwright/test";
import type { Component } from "vue";
import {
  isAdminNavigationExperience,
  isPlatformExperience,
  resolvePrimaryRole,
  resolveRoleExperience,
  roleExperienceNavigationLabel,
  type DashboardAction,
  type RoleExperienceDefinition,
} from "../../../resources/js/lib/role-experience";
import {
  mobileNavGridClass,
  resolveMemberMobileNavItems,
} from "../../../resources/js/lib/member-mobile-nav";
import {
  actionableQueueItems,
  primaryQueueItem,
} from "../../../resources/js/lib/admin-work-queue";
import {
  isPendingPayment,
  reconcilePaymentSelection,
  selectablePayments,
} from "../../../resources/js/lib/payment-selection";
import {
  contributionAmountHelper,
  isFixedContributionCode,
} from "../../../resources/js/lib/contribution-payment";

const action = (label: string, permission?: string): DashboardAction => ({
  label,
  description: label,
  href: `/${label.toLowerCase().replaceAll(" ", "-")}`,
  icon: {} as Component,
  permissions: permission ? [permission] : undefined,
});

const definitions: Record<string, RoleExperienceDefinition> = {
  "system-admin": {
    badge: "System Admin",
    title: "Platform",
    description: "Platform",
    actions: [],
  },
  "admin-pusat": {
    badge: "Admin Pusat",
    title: "Platform",
    description: "Platform",
    actions: [],
  },
  pengurus: {
    badge: "Pengurus Koperasi",
    title: "Pengurus",
    description: "Pengurus",
    actions: [action("Final approval", "approve_cooperative_loan")],
  },
  manajer: {
    badge: "Manajer Koperasi",
    title: "Manajer",
    description: "Manajer",
    actions: [action("Review", "review_cooperative_loan")],
  },
  "admin-koperasi": {
    badge: "Admin Koperasi",
    title: "Admin",
    description: "Admin",
    actions: [action("Validate member", "validate_cooperative_member")],
  },
  kasir: {
    badge: "Kasir Koperasi",
    title: "Kasir",
    description: "Kasir",
    actions: [action("POS", "access_cooperative_pos")],
  },
  "pos-operator": {
    badge: "Operator POS",
    title: "POS",
    description: "POS",
    actions: [action("POS", "access_cooperative_pos")],
  },
  generic: {
    badge: "Dashboard",
    title: "Generic",
    description: "Generic",
    actions: [],
  },
};

const fallback = { label: "Buka dashboard", href: "/dashboard" };

test.describe("role experience resolver", () => {
  test("keeps platform roles ahead of cooperative roles and permissions", () => {
    expect(
      resolveRoleExperience(
        ["System Admin", "Kasir Koperasi"],
        ["access_cooperative_pos"],
        definitions,
        fallback,
      ),
    ).toMatchObject({ key: "system-admin", badge: "System Admin" });
    expect(
      resolveRoleExperience(
        ["Admin Pusat", "Kasir Koperasi"],
        ["access_cooperative_pos"],
        definitions,
        fallback,
      ),
    ).toMatchObject({ key: "admin-pusat", badge: "Admin Pusat" });
    expect(resolvePrimaryRole(["Pengurus Koperasi", "Admin Koperasi"])).toBe(
      "pengurus",
    );
    expect(resolvePrimaryRole(["Manajer Koperasi", "Admin Koperasi"])).toBe(
      "manajer",
    );
    expect(resolvePrimaryRole(["Admin Koperasi", "Kasir Koperasi"])).toBe(
      "admin-koperasi",
    );
  });

  test("resolves explicit roles before permission-derived POS access", () => {
    expect(
      resolveRoleExperience(["Pengurus Koperasi"], [], definitions, fallback)
        .key,
    ).toBe("pengurus");
    expect(
      resolveRoleExperience(["Manajer Koperasi"], [], definitions, fallback)
        .key,
    ).toBe("manajer");
    expect(
      resolveRoleExperience(["Admin Koperasi"], [], definitions, fallback).key,
    ).toBe("admin-koperasi");
    expect(
      resolveRoleExperience(["Kasir Koperasi"], [], definitions, fallback).key,
    ).toBe("kasir");
    expect(
      resolveRoleExperience(
        [],
        ["access_cooperative_pos"],
        definitions,
        fallback,
      ),
    ).toMatchObject({
      key: "pos-operator",
      badge: "Operator POS",
    });
    expect(
      resolveRoleExperience(["Unknown"], [], definitions, fallback).key,
    ).toBe("generic");
  });

  test("filters actions and falls back to the dashboard CTA", () => {
    const withoutPermission = resolveRoleExperience(
      ["Admin Koperasi"],
      [],
      definitions,
      fallback,
    );
    expect(withoutPermission.actions).toHaveLength(0);
    expect(withoutPermission.ctaHref).toBe("/dashboard");

    const withPermission = resolveRoleExperience(
      ["Admin Koperasi"],
      ["validate_cooperative_member"],
      definitions,
      fallback,
    );
    expect(withPermission.actions).toHaveLength(1);
    expect(withPermission.ctaHref).not.toBe("");
    expect(withPermission.ctaHref).not.toBe("#");
  });

  test("does not grant cross-role approval or validation actions", () => {
    expect(
      resolveRoleExperience(
        ["Kasir Koperasi"],
        ["access_cooperative_pos", "cashier_store_credit"],
        definitions,
        fallback,
      ).actions.map(({ label }) => label),
    ).not.toContain("Validate member");
    expect(
      resolveRoleExperience(
        ["Admin Koperasi"],
        ["validate_cooperative_member"],
        definitions,
        fallback,
      ).actions.map(({ label }) => label),
    ).not.toContain("Final approval");
    expect(isPlatformExperience("system-admin")).toBe(true);
    expect(isPlatformExperience("admin-pusat")).toBe(true);
  });

  test("uses the primary role for sidebar navigation and labels", () => {
    expect(isAdminNavigationExperience("system-admin")).toBe(false);
    expect(isAdminNavigationExperience("pengurus")).toBe(false);
    expect(isAdminNavigationExperience("manajer")).toBe(false);
    expect(isAdminNavigationExperience("admin-koperasi")).toBe(true);
    expect(roleExperienceNavigationLabel("system-admin")).toBe("Platform");
    expect(roleExperienceNavigationLabel("pengurus")).toBe(
      "Ruang kerja Pengurus",
    );
    expect(roleExperienceNavigationLabel("manajer")).toBe(
      "Ruang kerja Manajer",
    );
    expect(roleExperienceNavigationLabel("admin-koperasi")).toBe(
      "Ruang kerja Admin Koperasi",
    );
  });
});

test.describe("member mobile navigation resolver", () => {
  test("uses lifecycle-safe item counts and grid columns", () => {
    expect(
      resolveMemberMobileNavItems({
        can_access_financial_features: false,
        can_access_onboarding: true,
        is_pending_review: false,
      }),
    ).toHaveLength(3);
    expect(
      resolveMemberMobileNavItems({
        can_access_financial_features: false,
        can_access_onboarding: true,
        is_pending_review: true,
      }),
    ).toHaveLength(3);
    expect(
      resolveMemberMobileNavItems({
        can_access_financial_features: false,
        can_access_onboarding: false,
        is_pending_review: false,
      }),
    ).toHaveLength(2);
    expect(
      resolveMemberMobileNavItems({
        can_access_financial_features: true,
        can_access_onboarding: false,
        is_pending_review: false,
      }),
    ).toHaveLength(5);
    expect(mobileNavGridClass(2)).toBe("grid-cols-2");
    expect(mobileNavGridClass(3)).toBe("grid-cols-3");
    expect(mobileNavGridClass(5)).toBe("grid-cols-5");
  });

  test("never exposes financial links to inactive members", () => {
    const hrefs = resolveMemberMobileNavItems({
      can_access_financial_features: false,
      can_access_onboarding: true,
      is_pending_review: true,
    }).map(({ href }) => href);

    expect(hrefs.some((href) => /savings|loans|rewards/.test(href))).toBe(
      false,
    );
    expect(hrefs).toContain("/member/onboarding");
    expect(hrefs).toContain("/member/profile");
  });
});

test.describe("Admin operational selection helpers", () => {
  test("hides zero-count queue items and falls back when all counts are zero", () => {
    const permitted = [
      { label: "Pembayaran", count: 2 },
      { label: "Anggota", count: 0 },
      { label: "Revisi", count: 1 },
    ];

    expect(actionableQueueItems(permitted).map(({ label }) => label)).toEqual([
      "Pembayaran",
      "Revisi",
    ]);
    expect(primaryQueueItem(permitted)?.label).toBe("Pembayaran");
    expect(
      primaryQueueItem(permitted.map((item) => ({ ...item, count: 0 }))),
    ).toBe(undefined);
  });

  test("only permits pending payments and reconciles stale selection", () => {
    const payments = [
      { id: 1, status: "PENDING" },
      { id: 2, status: "APPROVED" },
      { id: 3, status: "REJECTED" },
      { id: 4, status: "VOID" },
    ];

    expect(isPendingPayment(payments[0])).toBe(true);
    expect(selectablePayments(payments, true).map(({ id }) => id)).toEqual([1]);
    expect(selectablePayments(payments, false)).toEqual([]);
    expect(
      reconcilePaymentSelection(payments, payments, true).map(({ id }) => id),
    ).toEqual([1]);
    expect(reconcilePaymentSelection(payments, payments, false)).toEqual([]);
  });

  test("uses the backend default amount for every fixed contribution type", () => {
    expect(isFixedContributionCode("POKOK")).toBe(true);
    expect(isFixedContributionCode("WAJIB")).toBe(true);
    expect(
      contributionAmountHelper({
        code: "WAJIB",
        name: "Simpanan Wajib",
        default_amount: 75000,
      }),
    ).toContain("75.000");
  });
});
