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
                } finally {
                    await writeRuntimeReport(runtime, testInfo);
                }
            });
        }
    });
}
