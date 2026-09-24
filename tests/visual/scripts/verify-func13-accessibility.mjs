import fs from "node:fs";
import path from "node:path";

const screens = [
    "pos-closings-index-default",
    "pos-inventory-counts-index-default",
    "savings-withdrawals-index-default",
];

let failed = false;

for (const screen of screens) {
    const reportPath = path.resolve("ui-audit-output/accessibility", `${screen}--desktop.json`);

    if (!fs.existsSync(reportPath)) {
        console.error(`${screen}: BLOCKED (deterministic desktop report is missing)`);
        failed = true;
        continue;
    }

    const report = JSON.parse(fs.readFileSync(reportPath, "utf8"));
    const violations = (report.violations ?? [])
        .filter((violation) => ["critical", "serious"].includes(violation.impact ?? ""))
        .flatMap((violation) => (violation.nodes ?? []).map((node) => ({
            rule: violation.id,
            impact: violation.impact,
            target: node.target,
            html: node.html,
        })));

    if (violations.length > 0) {
        console.error(`${screen}: HOLD (${violations.length} critical/serious axe node(s))`);
        for (const violation of violations) {
            console.error(JSON.stringify(violation));
        }
        failed = true;
        continue;
    }

    console.log(`${screen}: PASS (no critical/serious axe nodes)`);
}

if (failed) {
    process.exitCode = 1;
}
