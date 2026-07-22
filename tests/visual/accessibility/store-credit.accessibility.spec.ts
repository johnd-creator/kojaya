import { test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { prepareStableScreen } from "../helpers/stable-screen";
import { StoreCreditPage } from "../pages/StoreCreditPage";

test("Saldo Toko index accessibility @accessibility", async ({ page }, testInfo) => {
    const runtime = attachRuntimeHealth(page, "store-credit-index-default");
    try {
        await prepareStableScreen(page);
        await new StoreCreditPage(page).gotoIndex();
        await auditAccessibility(page, testInfo, "store-credit-index-default");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});

test("Saldo Toko show accessibility @accessibility", async ({ page }, testInfo) => {
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

test("POS register accessibility @accessibility", async ({ page }, testInfo) => {
    const runtime = attachRuntimeHealth(page, "pos-register-default");
    try {
        await prepareStableScreen(page);
        await page.goto("/cooperative/pos");
        await page.getByRole("heading").first().waitFor({ state: "visible" });
        await auditAccessibility(page, testInfo, "pos-register-default");
    } finally {
        await writeRuntimeReport(runtime, testInfo);
    }
});
