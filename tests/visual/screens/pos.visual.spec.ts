import { test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { assertNoHorizontalOverflow, prepareStableScreen } from "../helpers/stable-screen";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";
import { PosPage } from "../pages/PosPage";

test("POS register default @visual", async ({ page }, testInfo) => {
    test.skip(!isAuditScopeIncluded(screen("pos-register-default")));
    test.skip(!["desktop", "tablet", "mobile"].includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, "pos-register-default");
    try {
        await prepareStableScreen(page);
        await new PosPage(page).goto();
        await assertNoHorizontalOverflow(page);
        await captureScreen(page, testInfo, screen("pos-register-default"));
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
