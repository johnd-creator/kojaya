import fs from "node:fs/promises";
import path from "node:path";
import { execFile } from "node:child_process";
import { promisify } from "node:util";
import { isAuditScopeIncluded, screenRegistry } from "./helpers/screen-registry";

const exec = promisify(execFile);
const viewportSizes: Record<string, { name: string; width: number; height: number }> = {
    desktop: { name: "desktop", width: 1440, height: 900 },
    tablet: { name: "tablet", width: 768, height: 1024 },
    mobile: { name: "mobile", width: 390, height: 844 },
};
const routeCoverageKeys = [
    "discovered_get_routes",
    "renderable_routes",
    "audited_routes",
    "excluded_routes",
    "uncovered_routes",
    "stale_registry_routes",
    "stale_exclusion_routes",
    "duplicate_screen_ids",
];

async function revision(command: string, fallback: string): Promise<string> {
    try {
        return (await exec("git", ["rev-parse", command])).stdout.trim() || fallback;
    } catch {
        return fallback;
    }
}

function requestedProjects(): string[] {
    const explicit = process.env.UI_AUDIT_REQUESTED_VIEWPORT ?? process.env.UI_AUDIT_VIEWPORT;
    if (explicit && explicit !== "all") {
        return explicit.split(",").filter((name) => name in viewportSizes);
    }

    const projectArgument = process.argv.find((argument) => argument.startsWith("--project="));
    if (projectArgument) {
        return projectArgument.slice("--project=".length).split(",").filter((name) => name in viewportSizes);
    }

    return ["desktop", "tablet", "mobile"];
}

function expectedScreens(projects: string[]): Array<{ definition: (typeof screenRegistry)[number]; project: string }> {
    return screenRegistry.filter(isAuditScopeIncluded).flatMap((definition) => projects
        .filter((project) => definition.visual && definition.viewport_policy.includes(project))
        .map((project) => ({ definition, project })));
}

export function pullRequestNumber(
    eventName: string = process.env.UI_AUDIT_EVENT_NAME ?? process.env.GITHUB_EVENT_NAME ?? "local",
    rawNumber: string | undefined = process.env.UI_AUDIT_PULL_REQUEST_NUMBER ?? process.env.GITHUB_EVENT_PULL_REQUEST_NUMBER,
): number | null {
    if (eventName !== "pull_request" || rawNumber === undefined || rawNumber.trim() === "") {
        return null;
    }

    const number = Number(rawNumber);
    return Number.isInteger(number) && number > 0 ? number : null;
}

async function canonicalRouteCoverage(outputDir: string, enforce: boolean): Promise<Record<string, unknown> | null> {
    const routeCoveragePath = path.join(outputDir, "coverage", "cooperative-route-coverage.json");

    try {
        const parsed = JSON.parse(await fs.readFile(routeCoveragePath, "utf8")) as Record<string, unknown>;
        const missingKeys = routeCoverageKeys.filter((key) => typeof parsed[key] !== "number");
        const invalidCounts = routeCoverageKeys.filter((key) => typeof parsed[key] === "number" && (parsed[key] as number) < 0);

        if (missingKeys.length > 0 || invalidCounts.length > 0) {
            throw new Error(`Invalid canonical route coverage: missing=${missingKeys.join(",")}, invalid=${invalidCounts.join(",")}`);
        }

        if (enforce && routeCoverageKeys.some((key) => (parsed[key] as number) !== 0 && key !== "discovered_get_routes" && key !== "renderable_routes" && key !== "audited_routes" && key !== "excluded_routes")) {
            throw new Error("Canonical route coverage contains uncovered, stale, or duplicate entries.");
        }

        return parsed;
    } catch (error) {
        if (enforce) {
            throw new Error(`Canonical route coverage is unavailable or invalid: ${error instanceof Error ? error.message : String(error)}`);
        }

        return null;
    }
}

function writeVisualCoverageMarkdown(coverage: Record<string, number>): string {
    return [
        "# Visual-entry coverage",
        "",
        `- Expected visual entries: ${coverage.expected_entries}`,
        `- Generated manifest entries: ${coverage.generated_entries}`,
        `- Passed: ${coverage.passed_entries}`,
        `- Failed: ${coverage.failed_entries}`,
        `- Skipped/not-run: ${coverage.skipped_entries}`,
        `- Desktop: ${coverage.desktop_expected_screens} expected / ${coverage.desktop_executed_screens} executed / ${coverage.desktop_passed_screens} passed`,
        `- Tablet: ${coverage.tablet_expected_screens} expected / ${coverage.tablet_executed_screens} executed / ${coverage.tablet_passed_screens} passed`,
        `- Mobile: ${coverage.mobile_expected_screens} expected / ${coverage.mobile_executed_screens} executed / ${coverage.mobile_passed_screens} passed`,
        "",
    ].join("\n");
}

export default async function globalTeardown(): Promise<void> {
    const outputDir = path.resolve("ui-audit-output");
    const fragmentsDir = path.join(outputDir, ".manifest-fragments");
    const files = await fs.readdir(fragmentsDir).catch(() => []);
    const screens: Record<string, unknown>[] = [];

    for (const file of files.sort()) {
        const content = await fs.readFile(path.join(fragmentsDir, file), "utf8");
        screens.push(JSON.parse(content) as Record<string, unknown>);
    }

    const actualKeys = new Set(screens.map((screen) => `${screen.id}:${(screen.viewport as { name: string }).name}`));
    const executionMode = process.env.UI_AUDIT_MODE ?? "compare";
    if (executionMode !== "accessibility") {
        for (const { definition, project } of expectedScreens(requestedProjects())) {
            const key = `${definition.id}:${project}`;
            if (actualKeys.has(key)) {
                continue;
            }

            const screenshotName = `${definition.module}--${definition.screen}--${definition.state}.png`;
            const viewport = viewportSizes[project];
            screens.push({
                id: definition.id,
                module: definition.module,
                screen: definition.screen,
                route_name: definition.route_name,
                route: definition.path_template,
                role: definition.role,
                auth_state: definition.auth_state,
                viewport,
                state: definition.state,
                fixture: definition.fixture,
                goal: definition.goal,
                primary_actions: definition.primary_actions,
                risk_level: definition.risk_level,
                comparison_status: "not-run",
                screenshot: null,
                expected_screenshot: `tests/visual/baselines/${project}/${screenshotName}`,
                actual_screenshot: null,
                diff_screenshot: null,
                runtime_report: null,
                accessibility_report: `accessibility/${definition.id}--${project}.json`,
                trace: null,
                error: "Expected scenario did not produce a manifest fragment.",
            });
        }
    }

    const ids = new Set<string>();
    for (const screen of screens) {
        const key = `${screen.id}:${(screen.viewport as { name: string }).name}`;
        if (ids.has(key)) {
            throw new Error(`Duplicate UI audit manifest entry: ${key}`);
        }
        ids.add(key);
    }

    const enforce = process.env.UI_AUDIT_ENFORCE_COVERAGE === "1";
    const eventName = process.env.UI_AUDIT_EVENT_NAME ?? process.env.GITHUB_EVENT_NAME ?? "local";
    const requestedMode = process.env.UI_AUDIT_REQUESTED_MODE ?? process.env.UI_AUDIT_MODE ?? "local";
    const requestedViewport = process.env.UI_AUDIT_REQUESTED_VIEWPORT ?? process.env.UI_AUDIT_VIEWPORT ?? "all";
    const requestedScope = process.env.UI_AUDIT_REQUESTED_SCOPE ?? process.env.UI_AUDIT_SCOPE ?? "all";
    const headSha = process.env.UI_AUDIT_HEAD_SHA
        ?? process.env.GITHUB_SHA
        ?? await revision("HEAD", "local");
    const testedSha = process.env.UI_AUDIT_TESTED_SHA ?? process.env.GITHUB_SHA ?? await revision("HEAD", "local");
    const baseSha = process.env.UI_AUDIT_BASE_SHA ?? process.env.GITHUB_BASE_SHA ?? "unknown";
    const defaultBranch = process.env.UI_AUDIT_DEFAULT_BRANCH ?? null;
    const defaultBranchSha = process.env.UI_AUDIT_DEFAULT_BRANCH_SHA ?? null;
    const shaFields = { head_sha: headSha, tested_sha: testedSha, base_sha: baseSha };
    if (enforce && Object.entries(shaFields).some(([, value]) => !/^[0-9a-f]{40}$/.test(value))) {
        throw new Error(`UI audit metadata contains an invalid SHA: ${JSON.stringify(shaFields)}`);
    }
    const passed = screens.filter((screen) => screen.comparison_status === "passed").length;
    const failed = screens.filter((screen) => screen.comparison_status === "failed").length;
    const skipped = screens.filter((screen) => ["skipped", "not-run"].includes(String(screen.comparison_status))).length;
    const expected = executionMode === "accessibility" ? 0 : expectedScreens(requestedProjects()).length;

    const coverage = {
        desktop_expected_screens: expectedScreens(["desktop"]).length,
        desktop_executed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status !== "not-run").length,
        desktop_passed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status === "passed").length,
        desktop_failed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status === "failed").length,
        desktop_skipped_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && ["skipped", "not-run"].includes(String(screen.comparison_status))).length,
        tablet_expected_screens: expectedScreens(["tablet"]).length,
        tablet_executed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "tablet" && screen.comparison_status !== "not-run").length,
        tablet_passed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "tablet" && screen.comparison_status === "passed").length,
        tablet_failed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "tablet" && screen.comparison_status === "failed").length,
        tablet_skipped_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "tablet" && ["skipped", "not-run"].includes(String(screen.comparison_status))).length,
        mobile_expected_screens: expectedScreens(["mobile"]).length,
        mobile_executed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "mobile" && screen.comparison_status !== "not-run").length,
        mobile_passed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "mobile" && screen.comparison_status === "passed").length,
        mobile_failed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "mobile" && screen.comparison_status === "failed").length,
        mobile_skipped_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "mobile" && ["skipped", "not-run"].includes(String(screen.comparison_status))).length,
        expected_entries: expected,
        generated_entries: screens.length,
        passed_entries: passed,
        failed_entries: failed,
        skipped_entries: skipped,
    };

    await fs.mkdir(outputDir, { recursive: true });
    const routeCoverage = await canonicalRouteCoverage(outputDir, enforce);
    await fs.writeFile(path.join(outputDir, "manifest.json"), `${JSON.stringify({
        application: "Kojaya",
        framework_version: 3,
        head_sha: headSha,
        tested_sha: testedSha,
        base_sha: baseSha,
        default_branch: defaultBranch,
        default_branch_sha: defaultBranchSha,
        event_name: eventName,
        pull_request_number: pullRequestNumber(),
        mode: requestedMode,
        viewport: requestedViewport,
        scope: requestedScope,
        generated_at: new Date().toISOString(),
        coverage,
        route_coverage: routeCoverage,
        screens,
    }, null, 2)}\n`);

    await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
    await fs.writeFile(path.join(outputDir, "coverage", "visual-entry-coverage.json"), `${JSON.stringify(coverage, null, 2)}\n`);
    await fs.writeFile(path.join(outputDir, "coverage", "visual-entry-coverage.md"), writeVisualCoverageMarkdown(coverage));

    if (enforce && (failed > 0 || skipped > 0 || screens.length !== expected)) {
        throw new Error(`UI audit coverage failed: expected=${expected}, generated=${screens.length}, failed=${failed}, skipped=${skipped}.`);
    }
}
