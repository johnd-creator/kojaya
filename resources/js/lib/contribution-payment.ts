import { formatCurrency } from "@/lib/formatters";

export const FIXED_CONTRIBUTION_CODES = ["POKOK", "WAJIB"] as const;

export type ContributionAmountOption = {
  code: string;
  name: string;
  default_amount: number | string;
};

export function isFixedContributionCode(code?: string | null): boolean {
  return FIXED_CONTRIBUTION_CODES.includes(
    code as (typeof FIXED_CONTRIBUTION_CODES)[number],
  );
}

export function contributionAmountHelper(
  type?: ContributionAmountOption,
): string {
  if (!type) {
    return "Pilih jenis simpanan untuk melihat aturan nominal.";
  }

  if (isFixedContributionCode(type.code)) {
    return `${type.name} ditetapkan ${formatCurrency(Number(type.default_amount))} per anggota.`;
  }

  return "Simpanan Sukarela bebas diisi sesuai nominal setoran anggota.";
}
