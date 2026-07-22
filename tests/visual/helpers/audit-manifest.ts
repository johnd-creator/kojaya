import fs from "node:fs/promises";
import path from "node:path";
import { expect, type Page, type TestInfo } from "@playwright/test";

export type AuditScreenDefinition = {
    id: string;
    module: string;
    screen: string;
    route: string;
    role: string;
    state: string;
    goal: string;
    primary_actions: string[];
    risk_level: "informational" | "operational" | "transactional";
    accessibility_report?: string;
};

const safe = (value: string): string => value.replace(/[^a-zA-Z0-9._-]+/g, "-");

export async function captureScreen(
    page: Page,
    testInfo: TestInfo,
    definition: AuditScreenDefinition,
): Promise<void> {
    const viewport = testInfo.project.use.viewport;
    if (!viewport) {
        throw new Error(`Viewport is required for ${definition.id}.`);
    }

    const viewportName = testInfo.project.name;
    const screenshotName = `${definition.module}--${definition.screen}--${definition.state}.png`;
    const screenshotPath = path.resolve(
        "ui-audit-output/screenshots",
        viewportName,
        screenshotName,
    );

    await fs.mkdir(path.dirname(screenshotPath), { recursive: true });
    await page.screenshot({ path: screenshotPath, fullPage: true, animations: "disabled" });

    const isCapture = process.env.UI_AUDIT_MODE === "capture";
    if (!isCapture) {
        await expect(page).toHaveScreenshot(screenshotName, {
            fullPage: true,
            animations: "disabled",
        });
    }

    const fragmentDir = path.resolve("ui-audit-output/.manifest-fragments");
    await fs.mkdir(fragmentDir, { recursive: true });
    const fragment = {
        ...definition,
        route: new URL(page.url()).pathname + new URL(page.url()).search,
        viewport: {
            name: viewportName,
            width: viewport.width,
            height: viewport.height,
        },
        screenshot: `screenshots/${viewportName}/${screenshotName}`,
        accessibility_report:
            definition.accessibility_report ?? `accessibility/${definition.id}--${viewportName}.json`,
    };

    await fs.writeFile(
        path.join(fragmentDir, `${safe(definition.id)}--${safe(viewportName)}.json`),
        JSON.stringify(fragment, null, 2) + "\n",
    );
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
