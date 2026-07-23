import { AxeBuilder } from "@axe-core/playwright";
import fs from "node:fs/promises";
import path from "node:path";
import { expect, type Page, type TestInfo } from "@playwright/test";
import { waitForStableScreen } from "./stable-screen";

type KnownFinding = {
    screen_id: string;
    rule: string;
    impact: "critical" | "serious" | "moderate" | "minor";
    selector: string;
    reason: string;
    tracking_id: string;
    expires_on: string;
};

type KnownFindingsFile = {
    findings: KnownFinding[];
};

async function readKnownFindings(): Promise<KnownFinding[]> {
    const file = JSON.parse(await fs.readFile(
        path.resolve("tests/visual/accessibility-known-findings.json"),
        "utf8",
    )) as KnownFindingsFile;

    for (const finding of file.findings) {
        if (new Date(`${finding.expires_on}T23:59:59+00:00`) < new Date()) {
            throw new Error(`Accessibility waiver expired: ${finding.tracking_id}`);
        }
    }

    return file.findings;
}

export async function auditAccessibility(
    page: Page,
    testInfo: TestInfo,
    screen: string,
): Promise<void> {
    await waitForStableScreen(page, { screenId: screen });
    const result = await new AxeBuilder({ page }).analyze();
    const knownFindings = await readKnownFindings();
    const blockingViolations = result.violations.filter((violation) =>
        ["critical", "serious"].includes(violation.impact ?? ""),
    );
    const knownKeys = new Set<string>();
    const newViolations = blockingViolations.flatMap((violation) => violation.nodes.flatMap((node) =>
        node.target.map((selector) => {
            const finding = knownFindings.find((known) => known.screen_id === screen
                && known.rule === violation.id
                && known.impact === violation.impact
                && known.selector === selector);

            if (finding) {
                knownKeys.add(`${finding.tracking_id}:${finding.selector}`);
            }

            return finding ? null : {
                rule: violation.id,
                impact: violation.impact,
                selector,
                help: violation.help,
                help_url: violation.helpUrl,
            };
        }),
    )).filter((violation): violation is NonNullable<typeof violation> => violation !== null);
    const staleFindings = knownFindings.filter((finding) => finding.screen_id === screen
        && ["critical", "serious"].includes(finding.impact)
        && !knownKeys.has(`${finding.tracking_id}:${finding.selector}`));
    const outputPath = path.resolve(
        "ui-audit-output/accessibility",
        `${screen}--${testInfo.project.name}.json`,
    );

    await fs.mkdir(path.dirname(outputPath), { recursive: true });
    await fs.writeFile(outputPath, JSON.stringify({
        ...result,
        ui_audit: {
            known_violations: blockingViolations.length - newViolations.length,
            new_violations: newViolations,
            stale_findings: staleFindings,
        },
    }, null, 2) + "\n");

    expect({ newViolations, staleFindings }, "New or stale critical/serious accessibility findings")
        .toEqual({ newViolations: [], staleFindings: [] });
}
