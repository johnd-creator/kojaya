import { test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen, assertNoHorizontalOverflow } from "../helpers/stable-screen";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";
import { MemberPage } from "../pages/MemberPage";

test("Anggota Koperasi default @visual", async ({ page }, testInfo) => {
    test.skip(!isAuditScopeIncluded(screen("members-index-default")));
    test.skip(!["desktop", "tablet", "mobile"].includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, "members-index-default");
    try {
        await prepareStableScreen(page);
        await new MemberPage(page).goto();
        if (["tablet", "mobile"].includes(testInfo.project.name)) {
            await assertNoHorizontalOverflow(page);
        }
        await captureScreen(page, testInfo, screen("members-index-default"));
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
