import {
  Gift,
  House,
  UserRound,
  WalletCards,
  WalletMinimal,
} from "lucide-vue-next";
import type { Component } from "vue";
import {
  dashboard as memberDashboard,
  loans as memberLoans,
  onboarding as memberOnboarding,
  profile as memberProfile,
  rewards as memberRewards,
  savings as memberSavings,
} from "@/routes/member";

export type MemberAccess = {
  can_access_financial_features: boolean;
  can_access_onboarding: boolean;
  is_pending_review: boolean;
};

export type MemberMobileNavItem = {
  label: string;
  href: string;
  icon: Component;
};

export function resolveMemberMobileNavItems(
  memberAccess: MemberAccess | null,
): MemberMobileNavItem[] {
  const base: MemberMobileNavItem[] = [
    { label: "Beranda", href: memberDashboard().url, icon: House },
  ];

  if (!memberAccess?.can_access_financial_features) {
    return [
      ...base,
      ...(memberAccess?.can_access_onboarding
        ? [
            {
              label: memberAccess.is_pending_review ? "Status" : "Onboarding",
              href: memberOnboarding().url,
              icon: WalletCards,
            },
          ]
        : []),
      { label: "Profil", href: memberProfile().url, icon: UserRound },
    ];
  }

  return [
    ...base,
    { label: "Simpanan", href: memberSavings().url, icon: WalletCards },
    { label: "Pinjaman", href: memberLoans().url, icon: WalletMinimal },
    { label: "Reward", href: memberRewards().url, icon: Gift },
    { label: "Profil", href: memberProfile().url, icon: UserRound },
  ];
}

export function mobileNavGridClass(itemCount: number): string {
  switch (itemCount) {
    case 2:
      return "grid-cols-2";
    case 3:
      return "grid-cols-3";
    case 4:
      return "grid-cols-4";
    default:
      return "grid-cols-5";
  }
}
