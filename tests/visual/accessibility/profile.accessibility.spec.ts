import { test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen } from "../helpers/stable-screen";
import { ProfilePage } from "../pages/ProfilePage";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";

test("Profil accessibility @accessibility", async ({ page }, testInfo) => {
    test.skip(!isAuditScopeIncluded(screen("profile-default")));
    const runtime = attachRuntimeHealth(page, "profile-default");
    try {
        await prepareStableScreen(page);
        await new ProfilePage(page).goto();
        await auditAccessibility(page, testInfo, "profile-default");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
