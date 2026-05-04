type NumericValue = number | string | null | undefined;

const numberFormatter = new Intl.NumberFormat("id-ID");
const currencyFormatter = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});
const dateFormatter = new Intl.DateTimeFormat("id-ID", {
  day: "2-digit",
  month: "short",
  year: "numeric",
});
const dateTimeFormatter = new Intl.DateTimeFormat("id-ID", {
  day: "2-digit",
  month: "short",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});

export function toNumber(value: NumericValue): number {
  if (value === null || value === undefined || value === "") {
    return 0;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : 0;
}

export function formatCurrency(amount: NumericValue): string {
  return currencyFormatter.format(toNumber(amount));
}

export function formatDate(date: string | null | undefined): string {
  if (!date) {
    return "-";
  }

  const parsed = new Date(date);

  return Number.isNaN(parsed.getTime()) ? "-" : dateFormatter.format(parsed);
}

export function formatDateTime(date: string | null | undefined): string {
  if (!date) {
    return "-";
  }

  const parsed = new Date(date);

  return Number.isNaN(parsed.getTime())
    ? "-"
    : dateTimeFormatter.format(parsed);
}

export function formatDateRange(
  start: string | null | undefined,
  end: string | null | undefined,
): string {
  return `${formatDate(start)} - ${formatDate(end)}`;
}

export function formatNumber(num: NumericValue): string {
  return numberFormatter.format(toNumber(num));
}

export function formatPercentage(value: NumericValue): string {
  return `${numberFormatter.format(toNumber(value))}%`;
}
