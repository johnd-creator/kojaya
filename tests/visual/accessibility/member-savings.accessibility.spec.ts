import { AxeBuilder } from "@axe-core/playwright";
import { expect, test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import {
  attachRuntimeHealth,
  writeRuntimeReport,
} from "../helpers/runtime-health";
import {
  installStableEnvironment,
  waitForStableScreen,
} from "../helpers/stable-screen";

test.use({ storageState: "tests/visual/.auth/anggota.json" });

for (const colorScheme of ["light", "dark"] as const) {
  const screenId = `member-savings-default-${colorScheme}`;

  test(`${screenId} @accessibility`, async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== "desktop");
    const runtime = attachRuntimeHealth(page, screenId);

    try {
      await page.emulateMedia({ colorScheme });
      await page.addInitScript((appearance) => {
        localStorage.setItem("appearance", appearance);
      }, colorScheme);
      await installStableEnvironment(page);
      const response = await page.goto("/member/savings", {
        waitUntil: "domcontentloaded",
      });

      expect(response?.status(), `${screenId} did not return an HTML page.`).toBe(200);
      await waitForStableScreen(page, { screenId: "member-savings-default" });
      if (colorScheme === "light") {
        await auditAccessibility(page, testInfo, screenId);
      } else {
        const result = await new AxeBuilder({ page })
          .include(".text-emerald-700")
          .analyze();
        const blockingViolations = result.violations.filter((violation) =>
          ["critical", "serious"].includes(violation.impact ?? ""),
        );

        expect(blockingViolations).toEqual([]);
      }
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}
