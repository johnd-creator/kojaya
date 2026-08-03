import { test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen } from "../helpers/stable-screen";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";
import { DashboardPage } from "../pages/DashboardPage";

test("Dashboard accessibility @accessibility", async ({ page }, testInfo) => {
    test.skip(!isAuditScopeIncluded(screen("dashboard-default")));
    const runtime = attachRuntimeHealth(page, "dashboard-default");
    try {
        await prepareStableScreen(page);
        await new DashboardPage(page).goto();
        await auditAccessibility(page, testInfo, "dashboard-default");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
