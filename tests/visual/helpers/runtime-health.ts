import fs from "node:fs/promises";
import path from "node:path";
import type { Page, TestInfo } from "@playwright/test";

export type RuntimeReport = {
    screen: string;
    page_errors: string[];
    console_errors: string[];
    console_warnings: string[];
    failed_requests: string[];
    unexpected_responses: string[];
};

const knownWarnings: Array<{ pattern: RegExp; reason: string }> = [];

export function attachRuntimeHealth(page: Page, screen: string): RuntimeReport {
    const report: RuntimeReport = {
        screen,
        page_errors: [],
        console_errors: [],
        console_warnings: [],
        failed_requests: [],
        unexpected_responses: [],
    };

    page.on("pageerror", (error) => report.page_errors.push(error.message));
    page.on("console", (message) => {
        if (message.type() === "error") {
            report.console_errors.push(message.text());
        }
        if (message.type() === "warning") {
            report.console_warnings.push(message.text());
        }
    });
    page.on("requestfailed", (request) => {
        const failure = request.failure()?.errorText ?? "unknown error";
        report.failed_requests.push(`${request.method()} ${request.url()} — ${failure}`);
    });
    page.on("response", (response) => {
        if ([403, 404, 500, 502, 503].includes(response.status())) {
            report.unexpected_responses.push(`${response.status()} ${response.request().method()} ${response.url()}`);
        }
    });

    return report;
}

export async function writeRuntimeReport(report: RuntimeReport, testInfo: TestInfo): Promise<void> {
    const project = testInfo.project.name;
    const fileName = `${report.screen}--${project}.json`;
    const outputPath = path.resolve("ui-audit-output/runtime", fileName);

    await fs.mkdir(path.dirname(outputPath), { recursive: true });
    await fs.writeFile(outputPath, `${JSON.stringify(report, null, 2)}\n`);

    const unexpectedWarnings = report.console_warnings.filter(
        (warning) => !knownWarnings.some(({ pattern }) => pattern.test(warning)),
    );
    const issues = [
        ...report.page_errors.map((error) => `pageerror: ${error}`),
        ...report.console_errors.map((error) => `console.error: ${error}`),
        ...report.failed_requests.map((request) => `requestfailed: ${request}`),
        ...report.unexpected_responses.map((response) => `unexpected response: ${response}`),
        ...unexpectedWarnings.map((warning) => `console.warn: ${warning}`),
    ];

    if (issues.length > 0) {
        throw new Error(`Runtime health issues for ${report.screen}:\n${issues.join("\n")}`);
    }
}
