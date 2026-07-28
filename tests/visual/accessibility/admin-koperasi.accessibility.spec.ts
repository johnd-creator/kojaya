import { expect, test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { installStableEnvironment, waitForStableScreen } from "../helpers/stable-screen";

test.use({ storageState: "tests/visual/.auth/admin.json" });

const scenarios = [
  ["admin-dashboard", "/dashboard"],
  ["admin-members", "/cooperative/members"],
  ["admin-payments", "/cooperative/payments"],
  ["admin-dues", "/cooperative/dues?period_scope=all&status=OPEN"],
] as const;

for (const [id, route] of scenarios) {
  test(id + " @accessibility", async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== "desktop");
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const response = await page.goto(route, { waitUntil: "domcontentloaded" });
      expect(response?.status(), id + " did not return an HTML page.").toBe(200);
      await waitForStableScreen(page, { screenId: id });
      await auditAccessibility(page, testInfo, id);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}
