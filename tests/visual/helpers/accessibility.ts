import { AxeBuilder } from "@axe-core/playwright";
import fs from "node:fs/promises";
import path from "node:path";
import { expect, type Locator, type Page, type TestInfo } from "@playwright/test";
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

export function accessibilityMetrics(input: {
    blockingRuleCount: number;
    blockingNodeCount: number;
    knownNodeCount: number;
    newNodeCount: number;
    staleFindingCount: number;
}): {
    blocking_rule_count: number;
    blocking_node_count: number;
    known_node_count: number;
    new_node_count: number;
    stale_finding_count: number;
    known_violations: number;
} {
    return {
        blocking_rule_count: Math.max(0, input.blockingRuleCount),
        blocking_node_count: Math.max(0, input.blockingNodeCount),
        known_node_count: Math.max(0, input.knownNodeCount),
        new_node_count: Math.max(0, input.newNodeCount),
        stale_finding_count: Math.max(0, input.staleFindingCount),
        known_violations: Math.max(0, input.knownNodeCount),
    };
}

async function readKnownFindings(): Promise<KnownFinding[]> {
    const file = JSON.parse(await fs.readFile(
        path.resolve("tests/visual/accessibility-known-findings.json"),
        "utf8",
    )) as KnownFindingsFile;

    const trackingIds = new Set<string>();
    const findingKeys = new Set<string>();

    for (const finding of file.findings) {
        if (trackingIds.has(finding.tracking_id)) {
            throw new Error(`Duplicate accessibility tracking ID: ${finding.tracking_id}`);
        }

        const findingKey = `${finding.screen_id}:${finding.rule}:${finding.selector}`;
        if (findingKeys.has(findingKey)) {
            throw new Error(`Duplicate accessibility finding: ${findingKey}`);
        }

        trackingIds.add(finding.tracking_id);
        findingKeys.add(findingKey);

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
    readyLocator?: Locator,
): Promise<void> {
    await waitForStableScreen(page, { screenId: screen, readyLocator });
    const result = await new AxeBuilder({ page }).analyze();
    const knownFindings = await readKnownFindings();
    const blockingViolations = result.violations.filter((violation) =>
        ["critical", "serious"].includes(violation.impact ?? ""),
    );
    const blockingNodes = blockingViolations.flatMap((violation) => violation.nodes.flatMap((node) =>
        node.target.map((selector) => ({ violation, node, selector })),
    ));
    const knownKeys = new Set<string>();
    const knownNodes = [];
    const newViolations = [];

    for (const { violation, node, selector } of blockingNodes) {
        const finding = knownFindings.find((known) => known.screen_id === screen
            && known.rule === violation.id
            && known.impact === violation.impact
            && known.selector === selector);

        if (finding) {
            const selectorCount = await page.locator(selector).count();
            expect(selectorCount, `Known accessibility selector must match exactly one node: ${finding.tracking_id}`)
                .toBe(1);
            knownKeys.add(`${finding.tracking_id}:${finding.selector}`);
            knownNodes.push({ rule: violation.id, impact: violation.impact, selector });
        } else {
            newViolations.push({
                rule: violation.id,
                impact: violation.impact,
                selector,
                help: violation.help,
                help_url: violation.helpUrl,
                html: node.html,
            });
        }
    }
    const staleFindings = knownFindings.filter((finding) => finding.screen_id === screen
        && ["critical", "serious"].includes(finding.impact)
        && !knownKeys.has(`${finding.tracking_id}:${finding.selector}`));
    const outputPath = path.resolve(
        "ui-audit-output/accessibility",
        `${screen}--${testInfo.project.name}.json`,
    );

    await fs.mkdir(path.dirname(outputPath), { recursive: true });
    const metrics = accessibilityMetrics({
        blockingRuleCount: blockingViolations.length,
        blockingNodeCount: blockingNodes.length,
        knownNodeCount: knownNodes.length,
        newNodeCount: newViolations.length,
        staleFindingCount: staleFindings.length,
    });

    await fs.writeFile(outputPath, JSON.stringify({
        ...result,
        ui_audit: {
            ...metrics,
            new_violations: newViolations,
            stale_findings: staleFindings,
        },
    }, null, 2) + "\n");

    expect({ newViolations, staleFindings }, "New or stale critical/serious accessibility findings")
        .toEqual({ newViolations: [], staleFindings: [] });
}
