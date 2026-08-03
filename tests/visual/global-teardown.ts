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

async function revision(command: string, fallback: string): Promise<string> {
    try {
        return (await exec("git", ["rev-parse", command])).stdout.trim() || fallback;
    } catch {
        return fallback;
    }
}

function requestedProjects(): string[] {
    const explicit = process.env.UI_AUDIT_VIEWPORT;
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
    eventName: string = process.env.GITHUB_EVENT_NAME ?? "local",
    rawNumber: string | undefined = process.env.GITHUB_EVENT_PULL_REQUEST_NUMBER,
): number | null {
    if (eventName !== "pull_request" || rawNumber === undefined || rawNumber.trim() === "") {
        return null;
    }

    const number = Number(rawNumber);
    return Number.isInteger(number) && number > 0 ? number : null;
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
    const mode = process.env.UI_AUDIT_MODE ?? "compare";
    if (mode !== "accessibility") {
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

    const headSha = process.env.GITHUB_EVENT_PULL_REQUEST_HEAD_SHA
        ?? process.env.GITHUB_SHA
        ?? await revision("HEAD", "local");
    const testedSha = process.env.GITHUB_TESTED_SHA ?? process.env.GITHUB_SHA ?? await revision("HEAD", "local");
    const baseSha = process.env.GITHUB_EVENT_PULL_REQUEST_BASE_SHA ?? process.env.GITHUB_BASE_SHA ?? "unknown";
    const passed = screens.filter((screen) => screen.comparison_status === "passed").length;
    const failed = screens.filter((screen) => screen.comparison_status === "failed").length;
    const skipped = screens.filter((screen) => ["skipped", "not-run"].includes(String(screen.comparison_status))).length;
    const expected = mode === "accessibility" ? 0 : expectedScreens(requestedProjects()).length;

    const coverage = {
        desktop_expected_screens: expectedScreens(["desktop"]).length,
        desktop_executed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status !== "not-run").length,
        desktop_passed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status === "passed").length,
        desktop_failed_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && screen.comparison_status === "failed").length,
        desktop_skipped_screens: screens.filter((screen) => (screen.viewport as { name: string }).name === "desktop" && ["skipped", "not-run"].includes(String(screen.comparison_status))).length,
        expected_entries: expected,
        generated_entries: screens.length,
        passed_entries: passed,
        failed_entries: failed,
        skipped_entries: skipped,
    };

    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(path.join(outputDir, "manifest.json"), `${JSON.stringify({
        application: "Kojaya",
        framework_version: 2,
        head_sha: headSha,
        tested_sha: testedSha,
        base_sha: baseSha,
        event_name: process.env.GITHUB_EVENT_NAME ?? "local",
        pull_request_number: pullRequestNumber(),
        generated_at: new Date().toISOString(),
        coverage,
        screens,
    }, null, 2)}\n`);

    await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
    await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.json"), `${JSON.stringify(coverage, null, 2)}\n`);
    await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.md"), [
        "# Cooperative UI audit coverage",
        "",
        `- Expected visual entries: ${expected}`,
        `- Generated manifest entries: ${screens.length}`,
        `- Passed: ${passed}`,
        `- Failed: ${failed}`,
        `- Skipped/not-run: ${skipped}`,
        `- Desktop expected: ${coverage.desktop_expected_screens}`,
        `- Desktop executed: ${coverage.desktop_executed_screens}`,
        `- Desktop failed: ${coverage.desktop_failed_screens}`,
        "",
    ].join("\n"));

    if (process.env.UI_AUDIT_ENFORCE_COVERAGE === "1" && (failed > 0 || skipped > 0 || screens.length !== expected)) {
        throw new Error(`UI audit coverage failed: expected=${expected}, generated=${screens.length}, failed=${failed}, skipped=${skipped}.`);
    }
}
