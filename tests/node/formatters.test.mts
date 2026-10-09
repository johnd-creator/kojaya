import assert from "node:assert/strict";
import test from "node:test";

import {
  formatCurrency,
  formatDate,
  formatDateRange,
  formatDateTime,
  formatNumber,
  formatPercentage,
  toNumber,
} from "../../resources/js/lib/formatters.ts";

test("boundary test: exact monetary precision for required amounts without IEEE-754 precision loss", () => {
  const boundaryCases = [
    { input: "0.10", expectedCurr: "Rp\u00A00,10", expectedNum: "0,10" },
    { input: "0.30", expectedCurr: "Rp\u00A00,30", expectedNum: "0,30" },
    { input: "100000.50", expectedCurr: "Rp\u00A0100.000,50", expectedNum: "100.000,50" },
    { input: "999999999999.99", expectedCurr: "Rp\u00A0999.999.999.999,99", expectedNum: "999.999.999.999,99" },
    { input: "90071992547409.93", expectedCurr: "Rp\u00A090.071.992.547.409,93", expectedNum: "90.071.992.547.409,93" },
    { input: "99999999999999.99", expectedCurr: "Rp\u00A099.999.999.999.999,99", expectedNum: "99.999.999.999.999,99" },
  ];

  for (const { input, expectedCurr, expectedNum } of boundaryCases) {
    assert.equal(formatCurrency(input), expectedCurr, `formatCurrency failed for ${input}`);
    assert.equal(formatNumber(input), expectedNum, `formatNumber failed for ${input}`);
  }
});

test("large negative financial totals preserve exact decimal precision and sign", () => {
  assert.equal(formatCurrency("-99999999999999.99"), "-Rp\u00A099.999.999.999.999,99");
  assert.equal(formatNumber("-99999999999999.99"), "-99.999.999.999.999,99");

  assert.equal(formatCurrency("-100000.50"), "-Rp\u00A0100.000,50");
  assert.equal(formatNumber("-100000.50"), "-100.000,50");

  assert.equal(formatCurrency("-0.30"), "-Rp\u00A00,30");
  assert.equal(formatNumber("-0.30"), "-0,30");
});

test("standard integer and zero-decimal values omit decimal places", () => {
  const integerCases = [
    { input: "100000", expectedCurr: "Rp\u00A0100.000", expectedNum: "100.000" },
    { input: "100000.00", expectedCurr: "Rp\u00A0100.000", expectedNum: "100.000" },
    { input: 100000, expectedCurr: "Rp\u00A0100.000", expectedNum: "100.000" },
    { input: "0", expectedCurr: "Rp\u00A00", expectedNum: "0" },
    { input: "0.00", expectedCurr: "Rp\u00A00", expectedNum: "0" },
    { input: 0, expectedCurr: "Rp\u00A00", expectedNum: "0" },
    { input: "-0", expectedCurr: "Rp\u00A00", expectedNum: "0" },
    { input: "-0.00", expectedCurr: "Rp\u00A00", expectedNum: "0" },
  ];

  for (const { input, expectedCurr, expectedNum } of integerCases) {
    assert.equal(formatCurrency(input), expectedCurr, `Integer currency mismatch for ${String(input)}`);
    assert.equal(formatNumber(input), expectedNum, `Integer number mismatch for ${String(input)}`);
  }
});

test("null, undefined, empty, and invalid inputs fail safe to zero", () => {
  const invalidCases = [null, undefined, "", "   ", "abc", "foo123", NaN, Infinity, -Infinity];

  for (const val of invalidCases) {
    assert.equal(formatCurrency(val), "Rp\u00A00", `Invalid currency mismatch for ${String(val)}`);
    assert.equal(formatNumber(val), "0", `Invalid number mismatch for ${String(val)}`);
  }
});

test("decimal padding and rounding behaves deterministically", () => {
  assert.equal(formatCurrency("100.5"), "Rp\u00A0100,50");
  assert.equal(formatNumber("100.5"), "100,50");

  assert.equal(formatCurrency("100.555"), "Rp\u00A0100,56");
  assert.equal(formatNumber("100.555"), "100,56");

  assert.equal(formatCurrency("100.554"), "Rp\u00A0100,55");
  assert.equal(formatNumber("100.554"), "100,55");

  assert.equal(formatCurrency("100.999"), "Rp\u00A0101");
  assert.equal(formatNumber("100.999"), "101");
});

test("toNumber, formatPercentage, and date formatting remain compatible", () => {
  assert.equal(toNumber(100), 100);
  assert.equal(toNumber("50000"), 50000);
  assert.equal(toNumber(null), 0);
  assert.equal(toNumber(undefined), 0);
  assert.equal(toNumber(""), 0);
  assert.equal(toNumber("abc"), 0);

  assert.equal(formatPercentage(15), "15%");
  assert.equal(formatPercentage("12.50"), "12,50%");

  assert.equal(formatDate(null), "-");
  assert.equal(formatDate(undefined), "-");
  const localDate = new Date(2026, 8, 30, 12, 0);
  const expectedDate = new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(localDate);
  assert.equal(formatDate(localDate.toISOString()), expectedDate);

  assert.equal(formatDateTime(null), "-");
  assert.equal(formatDateTime(undefined), "-");
  const expectedDateTime = new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(localDate);
  assert.equal(formatDateTime(localDate.toISOString()), expectedDateTime);

  const startDate = new Date(2026, 0, 1, 12, 0);
  const endDate = new Date(2026, 11, 31, 12, 0);
  const expectedRange = `${formatDate(startDate.toISOString())} - ${formatDate(endDate.toISOString())}`;
  assert.equal(formatDateRange(startDate.toISOString(), endDate.toISOString()), expectedRange);
});
