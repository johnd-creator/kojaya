import { AxeBuilder } from "@axe-core/playwright";
import fs from "node:fs/promises";
import path from "node:path";
import { expect, type Page, type TestInfo } from "@playwright/test";

export async function auditAccessibility(
    page: Page,
    testInfo: TestInfo,
    screen: string,
): Promise<void> {
    const result = await new AxeBuilder({ page }).analyze();
    const outputPath = path.resolve(
        "ui-audit-output/accessibility",
        `${screen}--${testInfo.project.name}.json`,
    );

    await fs.writeFile(outputPath, JSON.stringify(result, null, 2) + "\n");

    const blockingViolations = result.violations.filter((violation) =>
        ["critical", "serious"].includes(violation.impact ?? ""),
    );

    expect(blockingViolations, "Critical and serious accessibility violations").toEqual([]);
}
