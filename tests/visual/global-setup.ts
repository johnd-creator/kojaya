import fs from "node:fs/promises";
import path from "node:path";

export default async function globalSetup(): Promise<void> {
    const outputDir = path.resolve("ui-audit-output");

    await fs.rm(outputDir, { recursive: true, force: true });
    await fs.mkdir(path.join(outputDir, "screenshots"), { recursive: true });
    await fs.mkdir(path.join(outputDir, "accessibility"), { recursive: true });
    await fs.mkdir(path.join(outputDir, "runtime"), { recursive: true });
    await fs.mkdir(path.resolve("tests/visual/.auth"), { recursive: true });
}
