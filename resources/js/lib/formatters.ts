type NumericValue = number | string | null | undefined;

const numberFormatter = new Intl.NumberFormat("id-ID");
const numberWithDecimalsFormatter = new Intl.NumberFormat("id-ID", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const currencyFormatter = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});
const currencyWithDecimalsFormatter = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
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

function hasFractionalPart(value: NumericValue, numeric: number): boolean {
  if (typeof value === "string") {
    const trimmed = value.trim();
    if (trimmed.includes(".")) {
      const decimals = trimmed.split(".")[1] || "";
      return !/^0+$/.test(decimals);
    }
  }

  return numeric % 1 !== 0;
}

export function toNumber(value: NumericValue): number {
  if (value === null || value === undefined || value === "") {
    return 0;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : 0;
}

export function formatCurrency(amount: NumericValue): string {
  const num = toNumber(amount);

  return hasFractionalPart(amount, num)
    ? currencyWithDecimalsFormatter.format(num)
    : currencyFormatter.format(num);
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
  const val = toNumber(num);

  return hasFractionalPart(num, val)
    ? numberWithDecimalsFormatter.format(val)
    : numberFormatter.format(val);
}

export function formatPercentage(value: NumericValue): string {
  return `${numberFormatter.format(toNumber(value))}%`;
}
