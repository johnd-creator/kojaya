import fs from "node:fs/promises";
import path from "node:path";
import { expect, type Page, type TestInfo } from "@playwright/test";
import { waitForStableScreen } from "./stable-screen";

export type AuditScreenDefinition = {
    id: string;
    module: string;
    screen: string;
    route_name: string;
    path_template: string;
    route?: string;
    role: string;
    auth_state: string;
    state: string;
    fixture: string;
    ready_locator: string;
    goal: string;
    primary_actions: string[];
    risk_level: "informational" | "operational" | "transactional";
    viewport_policy: string[];
    visual: boolean;
    accessibility: boolean;
    accessibility_report?: string;
};

type ComparisonStatus = "passed" | "failed" | "captured" | "skipped" | "not-run";

const safe = (value: string): string => value.replace(/[^a-zA-Z0-9._-]+/g, "-");

function viewportFor(testInfo: TestInfo): { name: string; width: number; height: number } {
    const viewport = testInfo.project.use.viewport;

    if (!viewport) {
        throw new Error("Viewport is required for a UI audit screen.");
    }

    return {
        name: testInfo.project.name,
        width: viewport.width,
        height: viewport.height,
    };
}

async function writeFragment(
    fragmentPath: string,
    fragment: Record<string, unknown>,
): Promise<void> {
    await fs.mkdir(path.dirname(fragmentPath), { recursive: true });
    await fs.writeFile(fragmentPath, `${JSON.stringify(fragment, null, 2)}\n`);
}

export async function captureScreen(
    page: Page,
    testInfo: TestInfo,
    definition: AuditScreenDefinition,
): Promise<void> {
    const viewport = viewportFor(testInfo);
    const screenshotName = `${definition.module}--${definition.screen}--${definition.state}.png`;
    const screenshotPath = path.resolve(
        "ui-audit-output/screenshots",
        viewport.name,
        screenshotName,
    );
    const fragmentPath = path.resolve(
        "ui-audit-output/.manifest-fragments",
        `${safe(definition.id)}--${safe(viewport.name)}.json`,
    );
    const expectedPath = path.relative(
        process.cwd(),
        testInfo.snapshotPath(screenshotName),
    );
    const actualPath = path.relative(process.cwd(), screenshotPath);
    const runtimePath = `runtime/${definition.id}--${viewport.name}.json`;
    const accessibilityPath = definition.accessibility_report
        ?? `accessibility/${definition.id}--${viewport.name}.json`;
    const fragment = {
        id: definition.id,
        module: definition.module,
        screen: definition.screen,
        route_name: definition.route_name,
        route: new URL(page.url()).pathname + new URL(page.url()).search,
        role: definition.role,
        auth_state: definition.auth_state,
        viewport,
        state: definition.state,
        fixture: definition.fixture,
        goal: definition.goal,
        primary_actions: definition.primary_actions,
        risk_level: definition.risk_level,
        comparison_status: "not-run" as ComparisonStatus,
        screenshot: `screenshots/${viewport.name}/${screenshotName}`,
        expected_screenshot: expectedPath,
        actual_screenshot: actualPath,
        diff_screenshot: null,
        runtime_report: runtimePath,
        accessibility_report: accessibilityPath,
        trace: null,
        error: null,
    } satisfies Record<string, unknown>;

    await writeFragment(fragmentPath, fragment);

    try {
        await waitForStableScreen(page, { screenId: definition.id });
        await fs.mkdir(path.dirname(screenshotPath), { recursive: true });
        await page.screenshot({ path: screenshotPath, fullPage: true, animations: "disabled" });

        if (process.env.UI_AUDIT_MODE === "capture") {
            fragment.comparison_status = "captured";
        } else {
            await expect(page).toHaveScreenshot(screenshotName, {
                fullPage: true,
                animations: "disabled",
            });
            fragment.comparison_status = "passed";
        }
    } catch (error) {
        fragment.comparison_status = "failed";
        fragment.error = error instanceof Error ? error.message : String(error);
        throw error;
    } finally {
        await writeFragment(fragmentPath, fragment);
    }
}

export async function overrideInertiaProps(
    page: Page,
    urlPattern: string,
    update: (props: Record<string, unknown>) => void,
): Promise<void> {
    await page.route(urlPattern, async (route) => {
        const response = await route.fetch();
        const body = await response.text();
        const match = body.match(/data-page="([^"]*)"/);

        if (!match) {
            await route.fulfill({ response });
            return;
        }

        const decode = (value: string): string => value
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">")
            .replace(/&amp;/g, "&");
        const encode = (value: string): string => value
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        const pageData = JSON.parse(decode(match[1])) as { props: Record<string, unknown> };
        update(pageData.props);
        const updatedBody = body.replace(match[0], `data-page="${encode(JSON.stringify(pageData))}"`);
        await route.fulfill({ response, body: updatedBody });
    });
}
