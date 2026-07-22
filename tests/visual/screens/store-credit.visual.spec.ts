import { test } from "@playwright/test";
import { captureScreen, overrideInertiaProps } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { assertNoHorizontalOverflow, prepareStableScreen } from "../helpers/stable-screen";
import { screen } from "../helpers/screen-registry";
import { StoreCreditPage } from "../pages/StoreCreditPage";

const desktopOnly = (testInfo: { project: { name: string } }): void => {
    test.skip(testInfo.project.name !== "desktop");
};

const importantResponsive = (testInfo: { project: { name: string } }): void => {
    test.skip(!["desktop", "tablet", "mobile"].includes(testInfo.project.name));
};

async function finish(runtime: ReturnType<typeof attachRuntimeHealth>, testInfo: Parameters<typeof writeRuntimeReport>[1]): Promise<void> {
    await writeRuntimeReport(runtime, testInfo);
}

test("Saldo Toko index default @visual", async ({ page }, testInfo) => {
    importantResponsive(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-index-default");
    try {
        await prepareStableScreen(page);
        await new StoreCreditPage(page).gotoIndex();
        if (["tablet", "mobile"].includes(testInfo.project.name)) await assertNoHorizontalOverflow(page);
        await captureScreen(page, testInfo, screen("store-credit-index-default"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko index empty @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-index-empty");
    try {
        await prepareStableScreen(page);
        await overrideInertiaProps(page, "**/cooperative/store-credit?audit_state=empty", (props) => {
            const accounts = props.accounts as { data: unknown[]; meta: { links: unknown[] } };
            accounts.data = [];
            accounts.meta.links = [];
        });
        await new StoreCreditPage(page).gotoIndex("?audit_state=empty");
        await captureScreen(page, testInfo, screen("store-credit-index-empty"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko index search results @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-index-search-results");
    try {
        await prepareStableScreen(page);
        await new StoreCreditPage(page).gotoIndex("?q=UI%20Audit");
        await captureScreen(page, testInfo, screen("store-credit-index-search-results"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko index no results @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-index-no-results");
    try {
        await prepareStableScreen(page);
        await new StoreCreditPage(page).gotoIndex("?q=tidak-ada-hasil");
        await captureScreen(page, testInfo, screen("store-credit-index-no-results"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko open account dialog @visual", async ({ page }, testInfo) => {
    test.skip(!["desktop", "mobile"].includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, "store-credit-index-open-account-dialog");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openAccountDialog();
        await captureScreen(page, testInfo, screen("store-credit-index-open-account-dialog"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko validation error @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-index-validation-error");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openAccountDialog();
        const dialog = page.getByRole("dialog");
        await dialog.locator("select").evaluate((element) => element.removeAttribute("required"));
        await dialog.locator("form").evaluate((form) => form.requestSubmit());
        await page.locator("p.text-xs.text-red-500").first().waitFor({ state: "visible" });
        await captureScreen(page, testInfo, screen("store-credit-index-validation-error"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko show positive balance @visual", async ({ page }, testInfo) => {
    test.skip(!["desktop", "tablet", "mobile"].includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, "store-credit-show-positive-balance");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Positif");
        if (["tablet", "mobile"].includes(testInfo.project.name)) await assertNoHorizontalOverflow(page);
        await captureScreen(page, testInfo, screen("store-credit-show-positive-balance"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko show negative balance @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-show-negative-balance");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Saldo Negatif");
        await captureScreen(page, testInfo, screen("store-credit-show-negative-balance"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko show suspended @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-show-suspended");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Suspended");
        await captureScreen(page, testInfo, screen("store-credit-show-suspended"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko show empty ledger @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-show-empty-ledger");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Empty Ledger");
        await captureScreen(page, testInfo, screen("store-credit-show-empty-ledger"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko show with ledger @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-show-with-ledger");
    try {
        await prepareStableScreen(page);
        const storeCredit = new StoreCreditPage(page);
        await storeCredit.gotoIndex();
        await storeCredit.openDetailFor("UI Audit Positif");
        await captureScreen(page, testInfo, screen("store-credit-show-with-ledger"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko report local @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-report-local");
    try {
        await prepareStableScreen(page);
        await new StoreCreditPage(page).gotoReport();
        await captureScreen(page, testInfo, screen("store-credit-report-local"));
    } finally { await finish(runtime, testInfo); }
});

test.describe("Saldo Toko global report", () => {
    test.use({ storageState: "tests/visual/.auth/system-admin.json" });
    test("global @visual", async ({ page }, testInfo) => {
        desktopOnly(testInfo);
        const runtime = attachRuntimeHealth(page, "store-credit-report-global");
        try {
            await prepareStableScreen(page);
            await new StoreCreditPage(page).gotoReport();
            await captureScreen(page, testInfo, screen("store-credit-report-global"));
        } finally { await finish(runtime, testInfo); }
    });
});

test("Saldo Toko transfers pending @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-transfers-pending");
    try {
        await prepareStableScreen(page);
        await overrideInertiaProps(page, "**/cooperative/store-credit-transfers*", (props) => {
            const transfers = props.transfers as { data: Record<string, unknown>[] };
            transfers.data = transfers.data.map((transfer) => ({ ...transfer, has_proof: false }));
        });
        await new StoreCreditPage(page).gotoTransfers("?status=pending");
        await captureScreen(page, testInfo, screen("store-credit-transfers-pending"));
    } finally { await finish(runtime, testInfo); }
});

test("Saldo Toko transfers empty @visual", async ({ page }, testInfo) => {
    desktopOnly(testInfo);
    const runtime = attachRuntimeHealth(page, "store-credit-transfers-empty");
    try {
        await prepareStableScreen(page);
        await overrideInertiaProps(page, "**/cooperative/store-credit-transfers?audit_state=empty", (props) => {
            const transfers = props.transfers as { data: unknown[]; links: unknown[] };
            transfers.data = [];
            transfers.links = [];
        });
        await new StoreCreditPage(page).gotoTransfers("?audit_state=empty");
        await captureScreen(page, testInfo, screen("store-credit-transfers-empty"));
    } finally { await finish(runtime, testInfo); }
});
