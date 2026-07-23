import { test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { assertNoHorizontalOverflow, prepareStableScreen } from "../helpers/stable-screen";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";
import { ProfilePage } from "../pages/ProfilePage";

test("Profil default @visual", async ({ page }, testInfo) => {
    test.skip(!isAuditScopeIncluded(screen("profile-default")));
    const runtime = attachRuntimeHealth(page, "profile-default");
    try {
        await prepareStableScreen(page);
        await new ProfilePage(page).goto();
        if (["tablet", "mobile"].includes(testInfo.project.name)) {
            await assertNoHorizontalOverflow(page);
        }
        await captureScreen(page, testInfo, screen("profile-default"));
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
