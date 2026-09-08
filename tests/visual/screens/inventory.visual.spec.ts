import { expect, test } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { assertNoHorizontalOverflow, installStableEnvironment } from "../helpers/stable-screen";
import { isAuditScopeIncluded, screenRegistry } from "../helpers/screen-registry";

const manualScenarioIds = new Set([
    "dashboard-default",
    "members-index-default",
    "pos-register-default",
    "profile-default",
    "store-credit-index-default",
    "store-credit-index-empty",
    "store-credit-index-search-results",
    "store-credit-index-no-results",
    "store-credit-index-open-account-dialog",
    "store-credit-index-validation-error",
    "store-credit-show-positive-balance",
    "store-credit-show-negative-balance",
    "store-credit-show-suspended",
    "store-credit-show-empty-ledger",
    "store-credit-show-with-ledger",
    "store-credit-report-local",
    "store-credit-report-global",
    "store-credit-transfers-pending",
    "store-credit-transfers-empty",
    "admin-dashboard-dashboard-admin-koperasi",
    "admin-members-index-default",
    "admin-members-index-pending-filter",
    "admin-members-index-no-results",
    "admin-members-show-pending-review",
    "admin-members-show-revision",
    "admin-members-show-active",
    "admin-payments-index-pending",
    "admin-payments-index-empty",
    "admin-payments-index-selected",
    "admin-dues-index-open",
    "admin-dues-index-partial",
    "admin-dues-index-no-results",
    "admin-ledger-index-default",
    "admin-loans-index-default",
    "admin-loan-types-index-default",
    "admin-points-index-default",
]);

const inventoryScenarios = screenRegistry.filter((definition) => isAuditScopeIncluded(definition) && !manualScenarioIds.has(definition.id));
const authStates = [...new Set(inventoryScenarios.map((definition) => definition.auth_state))];

async function resolveRoute(page: Parameters<typeof installStableEnvironment>[0], definition: typeof inventoryScenarios[number]): Promise<string> {
    const fixtureToken = definition.path_template.match(/\{([^}]+)\}/g);
    if (!fixtureToken) {
        return definition.path_template;
    }

    const response = await page.request.get("/__ui-audit/fixtures");
    expect(response.ok(), `Fixture endpoint failed for ${definition.id}.`).toBe(true);
    const fixtures = (await response.json()) as Record<string, string | number | null>;
    const value = fixtures[definition.fixture];
    expect(value, `Undefined fixture ${definition.fixture} for ${definition.id}.`).toBeDefined();
    expect(value, `Missing fixture ${definition.fixture} for ${definition.id}.`).not.toBeNull();

    return definition.path_template.replace(/\{[^}]+\}/g, String(value));
}

for (const authState of authStates) {
    test.describe(`inventory ${authState}`, () => {
        test.use({ storageState: `tests/visual/.auth/${authState}.json` });

        for (const definition of inventoryScenarios.filter((item) => item.auth_state === authState)) {
            test(`${definition.id} @visual @inventory`, async ({ page }, testInfo) => {
                test.skip(!definition.viewport_policy.includes(testInfo.project.name));

                const runtime = attachRuntimeHealth(page, definition.id);
                try {
                    await installStableEnvironment(page);
                    const route = await resolveRoute(page, definition);
                    const response = await page.goto(route, { waitUntil: "domcontentloaded" });
                    expect(response?.status(), `${definition.id} did not return an HTML page.`).toBe(200);
                    if (["tablet", "mobile"].includes(testInfo.project.name)) {
                        await assertNoHorizontalOverflow(page);
                    }
                    await captureScreen(page, testInfo, definition);
                    if (definition.id === "pos-closings-index-default") {
                        const organizationId = new URL(page.url()).searchParams.get("organization_id");
                        expect(organizationId).toBeTruthy();
                        const date = { desktop: "2099-01-01", tablet: "2099-01-02", mobile: "2099-01-03" }[testInfo.project.name];
                        expect(date).toBeDefined();
                        await page.locator('input[type="date"]').fill(date!);
                        await page.getByRole("button", { name: "Terapkan", exact: true }).click();
                        await expect(page).toHaveURL((url) => url.searchParams.get("date") === date
                            && url.searchParams.get("organization_id") === organizationId);

                        const closingRequest = page.waitForRequest((request) => request.method() === "POST"
                            && new URL(request.url()).pathname === "/cooperative/pos/closings");
                        await page.getByRole("button", { name: "Tutup Hari Ini", exact: true }).click();
                        expect((await closingRequest).postDataJSON()).toMatchObject({ date, organization_id: organizationId });
                        await expect(page.getByRole("button", { name: "Sudah Ditutup", exact: true })).toBeDisabled();
                        await page.reload();
                        await expect(page.getByRole("button", { name: "Sudah Ditutup", exact: true })).toBeDisabled();
                    }
                } finally {
                    await writeRuntimeReport(runtime, testInfo);
                }
            });
        }
    });
}
