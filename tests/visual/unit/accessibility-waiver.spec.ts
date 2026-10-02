import { expect, test } from "@playwright/test";
import { expiredAccessibilityFindings } from "../helpers/accessibility";

test("waiver expires immediately after its UTC expiry date", () => {
    const finding = { expires_on: "2026-09-30", tracking_id: "fixture" };
    expect(expiredAccessibilityFindings([finding], new Date("2026-09-30T23:59:59Z"))).toEqual([]);
    expect(expiredAccessibilityFindings([finding], new Date("2026-10-01T00:00:00Z"))).toEqual([finding]);
});

test("expired waivers remain blocking even for unrelated screen audits", () => {
    const expired = { expires_on: "2026-09-30", screen_id: "other-screen" };
    const current = { expires_on: "2026-10-31", screen_id: "this-screen" };
    expect(expiredAccessibilityFindings([expired, current], new Date("2026-10-01T00:00:00Z"))).toEqual([expired]);
});
