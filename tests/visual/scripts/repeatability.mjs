import { createHash } from "node:crypto";
import { readdirSync, readFileSync, statSync } from "node:fs";
import { resolve } from "node:path";

const left = resolve(process.env.UI_AUDIT_REPEATABILITY_LEFT ?? "ui-audit-repeatability/a");
const right = resolve(process.env.UI_AUDIT_REPEATABILITY_RIGHT ?? "ui-audit-repeatability/b");
const hashes = (root) => {
    const result = new Map();
    const visit = (directory) => {
        for (const entry of readdirSync(directory, { withFileTypes: true })) {
            const full = resolve(directory, entry.name);
            if (entry.isDirectory()) visit(full);
            else if (entry.name.endsWith(".png")) result.set(full.slice(root.length + 1), createHash("sha256").update(readFileSync(full)).digest("hex"));
        }
    };
    visit(root);
    return result;
};
if (!statSync(left, { throwIfNoEntry: false }) || !statSync(right, { throwIfNoEntry: false })) {
    console.error("Repeatability requires two clean capture directories. Set UI_AUDIT_REPEATABILITY_LEFT and UI_AUDIT_REPEATABILITY_RIGHT.");
    process.exit(1);
}
const a = hashes(left);
const b = hashes(right);
const differences = [...new Set([...a.keys(), ...b.keys()])].filter((file) => a.get(file) !== b.get(file));
console.log(JSON.stringify({ left: a.size, right: b.size, unexpected_hash_differences: differences.length }, null, 2));
if (differences.length) process.exit(1);
