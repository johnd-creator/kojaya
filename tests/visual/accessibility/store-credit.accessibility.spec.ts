import { test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen } from "../helpers/stable-screen";
import { StoreCreditPage } from "../pages/StoreCreditPage";
import { isAuditScopeIncluded, screen } from "../helpers/screen-registry";

function skipIfOutOfScope(id: string): void {
    test.skip(!isAuditScopeIncluded(screen(id)));
}

function skipUnlessDesktop(testInfo: { project: { name: string } }): void {
    test.skip(testInfo.project.name !== "desktop");
}

test("Saldo Toko show accessibility @accessibility", async ({ page }, testInfo) => {
    skipUnlessDesktop(testInfo);
    skipIfOutOfScope("store-credit-show-positive-balance");
    const runtime = attachRuntimeHealth(page, "store-credit-show-positive-balance");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Positif");
        await auditAccessibility(page, testInfo, "store-credit-show-positive-balance");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});

test("Saldo Toko open account dialog accessibility @accessibility", async ({ page }, testInfo) => {
    skipUnlessDesktop(testInfo);
    skipIfOutOfScope("store-credit-index-open-account-dialog");
    const runtime = attachRuntimeHealth(page, "store-credit-index-open-account-dialog");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openAccountDialog();
        await auditAccessibility(page, testInfo, "store-credit-index-open-account-dialog");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
