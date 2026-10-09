type NumericValue = number | string | null | undefined;

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

interface ParsedNumeric {
  isNegative: boolean;
  intPart: string;
  decPart: string;
  hasDecimals: boolean;
}

/**
 * Parses any supported NumericValue into canonical string parts without passing
 * through IEEE-754 floating-point numbers, preventing precision loss for large financial values.
 */
function parseCanonicalNumeric(value: NumericValue): ParsedNumeric {
  if (value === null || value === undefined || value === "") {
    return { isNegative: false, intPart: "0", decPart: "00", hasDecimals: false };
  }

  let str = "";
  if (typeof value === "number") {
    if (!Number.isFinite(value)) {
      return { isNegative: false, intPart: "0", decPart: "00", hasDecimals: false };
    }
    if (Number.isSafeInteger(value)) {
      str = BigInt(value).toString();
    } else {
      str = value.toString();
      if (str.includes("e") || str.includes("E")) {
        str = value.toFixed(2);
      }
    }
  } else if (typeof value === "string") {
    str = value.trim();
  } else {
    return { isNegative: false, intPart: "0", decPart: "00", hasDecimals: false };
  }

  // Check valid canonical numeric string format (+/- digits.decimals)
  if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(str)) {
    return { isNegative: false, intPart: "0", decPart: "00", hasDecimals: false };
  }

  let isNegative = false;
  if (str.startsWith("-")) {
    isNegative = true;
    str = str.slice(1);
  } else if (str.startsWith("+")) {
    str = str.slice(1);
  }

  const dotIndex = str.indexOf(".");
  let intPart = dotIndex !== -1 ? str.slice(0, dotIndex) : str;
  let decPart = dotIndex !== -1 ? str.slice(dotIndex + 1) : "";

  // Strip leading zeroes from integer part
  intPart = intPart.replace(/^0+(?=\d)/, "");
  if (intPart === "") {
    intPart = "0";
  }

  // Handle rounding if more than 2 decimal digits
  if (decPart.length > 2) {
    const roundDigit = Number(decPart[2]);
    let cents = BigInt(intPart) * 100n + BigInt(decPart.slice(0, 2));
    if (roundDigit >= 5) {
      cents += 1n;
    }
    intPart = (cents / 100n).toString();
    decPart = (cents % 100n).toString().padStart(2, "0");
  } else if (decPart.length === 1) {
    decPart = `${decPart}0`;
  } else if (decPart.length === 0) {
    decPart = "00";
  }

  const hasDecimals = decPart !== "00";

  // Normalize negative zero to positive zero
  if (intPart === "0" && !hasDecimals) {
    isNegative = false;
  }

  return { isNegative, intPart, decPart, hasDecimals };
}

export function toNumber(value: NumericValue): number {
  if (value === null || value === undefined || value === "") {
    return 0;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : 0;
}

export function formatCurrency(amount: NumericValue): string {
  const { isNegative, intPart, decPart, hasDecimals } = parseCanonicalNumeric(amount);
  const groupedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  const prefix = isNegative ? "-Rp\u00A0" : "Rp\u00A0";

  return hasDecimals ? `${prefix}${groupedInt},${decPart}` : `${prefix}${groupedInt}`;
}

export function formatNumber(num: NumericValue): string {
  const { isNegative, intPart, decPart, hasDecimals } = parseCanonicalNumeric(num);
  const groupedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  const prefix = isNegative ? "-" : "";

  return hasDecimals ? `${prefix}${groupedInt},${decPart}` : `${prefix}${groupedInt}`;
}

export function formatPercentage(value: NumericValue): string {
  return `${formatNumber(value)}%`;
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
