import { expect, test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import {
  attachRuntimeHealth,
  writeRuntimeReport,
} from "../helpers/runtime-health";
import {
  assertNoHorizontalOverflow,
  installStableEnvironment,
} from "../helpers/stable-screen";
import { screen } from "../helpers/screen-registry";

test.use({ storageState: "tests/visual/.auth/admin.json" });

const scenarios = [
  ["admin-dashboard-dashboard-admin-koperasi", "/dashboard"],
  ["admin-members-index-default", "/cooperative/members"],
  [
    "admin-members-index-pending-filter",
    "/cooperative/members?validation_status=PENDING",
  ],
  [
    "admin-members-index-no-results",
    "/cooperative/members?search=UI-NO-RESULT-999",
  ],
  ["admin-payments-index-pending", "/cooperative/payments"],
  ["admin-payments-index-empty", "/cooperative/payments?status=APPROVED"],
  ["admin-dues-index-open", "/cooperative/dues?period_scope=all&status=OPEN"],
  ["admin-dues-index-partial", "/cooperative/dues?status=PARTIAL"],
  [
    "admin-dues-index-no-results",
    "/cooperative/dues?member_search=UI-NO-RESULT-999",
  ],
] as const;

for (const [id, route] of scenarios) {
  test(id + " @visual", async ({ page }, testInfo) => {
    const definition = screen(id);
    test.skip(!definition.viewport_policy.includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const response = await page.goto(route, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status(), id + " did not return an HTML page.").toBe(
        200,
      );
      if (["tablet", "mobile"].includes(testInfo.project.name)) {
        await assertNoHorizontalOverflow(page);
      }
      await captureScreen(page, testInfo, definition);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}

const detailScenarios = [
  ["admin-members-show-pending-review", "member-pending-review"],
  ["admin-members-show-revision", "member-revision"],
  ["admin-members-show-active", "member-positive"],
] as const;

for (const [id, fixtureKey] of detailScenarios) {
  test(id + " @visual", async ({ page }, testInfo) => {
    const definition = screen(id);
    test.skip(!definition.viewport_policy.includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const fixtureResponse = await page.request.get("/__ui-audit/fixtures");
      expect(
        fixtureResponse.ok(),
        "Fixture endpoint failed for " + id + ".",
      ).toBe(true);
      const fixtures = (await fixtureResponse.json()) as Record<
        string,
        string | number
      >;
      const memberId = fixtures[fixtureKey];
      expect(
        memberId,
        "Missing fixture " + fixtureKey + " for " + id + ".",
      ).toBeTruthy();
      const response = await page.goto("/cooperative/members/" + memberId, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status(), id + " did not return an HTML page.").toBe(
        200,
      );
      if (["tablet", "mobile"].includes(testInfo.project.name)) {
        await assertNoHorizontalOverflow(page);
      }
      await captureScreen(page, testInfo, definition);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}

test("admin-payments-index-selected @visual", async ({ page }, testInfo) => {
  const id = "admin-payments-index-selected";
  const definition = screen(id);
  test.skip(!definition.viewport_policy.includes(testInfo.project.name));
  const runtime = attachRuntimeHealth(page, id);

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/payments", {
      waitUntil: "domcontentloaded",
    });
    expect(response?.status(), id + " did not return an HTML page.").toBe(200);
    await page.locator("table tbody input[type=checkbox]").first().check();
    await captureScreen(page, testInfo, definition);
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});
