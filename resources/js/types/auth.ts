import type { ServerPrimaryRole } from "@/lib/role-experience";

export type User = {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
};

export type Auth = {
  user: User | null;
  roles?: string[];
  permissions?: string[];
  primary_role?: ServerPrimaryRole | null;
  member_access?: {
    is_active: boolean;
    is_pending_review: boolean;
    can_access_financial_features: boolean;
    can_access_onboarding: boolean;
  } | null;
};

export type TwoFactorConfigContent = {
  title: string;
  description: string;
  buttonText: string;
};
