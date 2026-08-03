import path from "node:path";
import { prepareAuditOutput } from "./global-setup-helpers.mjs";

export default async function globalSetup(): Promise<void> {
    await prepareAuditOutput({
        outputDir: path.resolve("ui-audit-output"),
        authDir: path.resolve("tests/visual/.auth"),
        cwd: path.resolve("."),
    });
}
