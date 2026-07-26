import { expect, test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import { attachRuntimeHealth, writeRuntimeReport } from "../helpers/runtime-health";
import { installStableEnvironment, waitForStableScreen } from "../helpers/stable-screen";
import { assertUniqueAccessibilityOwners, isAuditScopeIncluded, screenRegistry } from "../helpers/screen-registry";

assertUniqueAccessibilityOwners();

const entries = screenRegistry.filter((entry) => isAuditScopeIncluded(entry) && entry.accessibility && entry.state === "default");
const authStates = [...new Set(entries.map((entry) => entry.auth_state))];

async function resolveRoute(page: Parameters<typeof installStableEnvironment>[0], entry: typeof entries[number]): Promise<string> {
    if (!entry.path_template.includes("{")) {
        return entry.path_template;
    }

    const response = await page.request.get("/__ui-audit/fixtures");
    expect(response.ok(), `Fixture endpoint failed for ${entry.id}.`).toBe(true);
    const fixtures = (await response.json()) as Record<string, string | number | null>;
    const fixture = fixtures[entry.fixture];
    expect(fixture, `Missing fixture ${entry.fixture} for ${entry.id}.`).not.toBeNull();

    return entry.path_template.replace(/\{[^}]+\}/g, String(fixture));
}

for (const authState of authStates) {
    test.describe(`accessibility ${authState}`, () => {
        test.use({ storageState: `tests/visual/.auth/${authState}.json` });

        for (const entry of entries.filter((item) => item.auth_state === authState)) {
            test(`${entry.id} @accessibility`, async ({ page }, testInfo) => {
                test.skip(testInfo.project.name !== "desktop" || !entry.viewport_policy.includes(testInfo.project.name));
                const runtime = attachRuntimeHealth(page, entry.id);

                try {
                    await installStableEnvironment(page);
                    const response = await page.goto(await resolveRoute(page, entry), { waitUntil: "domcontentloaded" });
                    expect(response?.status(), `${entry.id} did not return an HTML page.`).toBe(200);
                    const readyLocator = entry.route_name === "cooperative.pos.transactions.receipt"
                        ? page.locator("body")
                        : undefined;
                    await waitForStableScreen(page, { screenId: entry.id, readyLocator });
                    await auditAccessibility(page, testInfo, entry.id, readyLocator);
                } finally {
                    await writeRuntimeReport(runtime, testInfo);
                }
            });
        }
    });
}
