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
} from "./formatters.ts";

test("canonical formatCurrency and formatNumber preserve exact monetary precision", () => {
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
