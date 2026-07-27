import { test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import {
  attachRuntimeHealth,
  writeRuntimeReport,
} from "../helpers/runtime-health";
import {
  prepareStableScreen,
  assertNoHorizontalOverflow,
} from "../helpers/stable-screen";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";
import { DashboardPage } from "../pages/DashboardPage";

test.use({ storageState: "tests/visual/.auth/system-admin.json" });

test("Dashboard default @visual", async ({ page }, testInfo) => {
  test.skip(!isAuditScopeIncluded(screen("dashboard-default")));
  const runtime = attachRuntimeHealth(page, "dashboard-default");
  try {
    await prepareStableScreen(page);
    await new DashboardPage(page).goto();
    if (["tablet", "mobile"].includes(testInfo.project.name)) {
      await assertNoHorizontalOverflow(page);
    }
    await captureScreen(page, testInfo, screen("dashboard-default"));
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});
