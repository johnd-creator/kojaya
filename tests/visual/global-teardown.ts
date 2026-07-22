import fs from "node:fs/promises";
import path from "node:path";

export default async function globalTeardown(): Promise<void> {
    const outputDir = path.resolve("ui-audit-output");
    const fragmentsDir = path.join(outputDir, ".manifest-fragments");
    const files = await fs.readdir(fragmentsDir).catch(() => []);
    const screens = [];

    for (const file of files.sort()) {
        const content = await fs.readFile(path.join(fragmentsDir, file), "utf8");
        const screen = JSON.parse(content) as { accessibility_report?: string } & Record<string, unknown>;

        if (screen.accessibility_report) {
            try {
                await fs.access(path.join(outputDir, screen.accessibility_report));
            } catch {
                delete screen.accessibility_report;
            }
        }

        screens.push(screen);
    }

    const ids = new Set<string>();
    for (const screen of screens) {
        const key = `${screen.id}:${screen.viewport.name}`;
        if (ids.has(key)) {
            throw new Error(`Duplicate UI audit manifest entry: ${key}`);
        }
        ids.add(key);
    }

    let commitSha = process.env.GITHUB_SHA;
    if (!commitSha) {
        try {
            const { execFile } = await import("node:child_process");
            const { promisify } = await import("node:util");
            commitSha = (await promisify(execFile)("git", ["rev-parse", "HEAD"])).stdout.trim();
        } catch {
            commitSha = "local";
        }
    }

    await fs.writeFile(
        path.join(outputDir, "manifest.json"),
        JSON.stringify(
            {
                application: "Kojaya",
                framework_version: 1,
                commit_sha: commitSha,
                generated_at: new Date().toISOString(),
                screens,
            },
            null,
            2,
        ) + "\n",
    );
}
