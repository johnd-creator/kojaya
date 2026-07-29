export type PaymentSelectionRow = {
  id: number;
  status: string;
};

export function isPendingPayment(row: PaymentSelectionRow): boolean {
  return row.status === "PENDING";
}

export function selectablePayments<T extends PaymentSelectionRow>(
  rows: T[],
  canApprove: boolean,
): T[] {
  return canApprove ? rows.filter(isPendingPayment) : [];
}

export function reconcilePaymentSelection<T extends PaymentSelectionRow>(
  selected: T[],
  rows: T[],
  canApprove: boolean,
): T[] {
  const selectableIds = new Set(
    selectablePayments(rows, canApprove).map((row) => row.id),
  );

  return selected.filter((row) => selectableIds.has(row.id));
}
