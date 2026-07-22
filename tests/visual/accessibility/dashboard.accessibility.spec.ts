import { test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen } from "../helpers/stable-screen";
import { DashboardPage } from "../pages/DashboardPage";

test("Dashboard accessibility @accessibility", async ({ page }, testInfo) => {
    const runtime = attachRuntimeHealth(page, "dashboard-default");
    try {
        await prepareStableScreen(page);
        await new DashboardPage(page).goto();
        await auditAccessibility(page, testInfo, "dashboard-default");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
