import { test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen, assertNoHorizontalOverflow } from "../helpers/stable-screen";
import { screen } from "../helpers/screen-registry";
import { MemberPage } from "../pages/MemberPage";

test("Anggota Koperasi default @visual", async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== "desktop");
    const runtime = attachRuntimeHealth(page, "members-index-default");
    try {
        await prepareStableScreen(page);
        await new MemberPage(page).goto();
        await captureScreen(page, testInfo, screen("members-index-default"));
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
