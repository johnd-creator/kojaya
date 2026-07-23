import { readdirSync, readFileSync, statSync } from "node:fs";
import { resolve } from "node:path";

const registry = JSON.parse(readFileSync(resolve("tests/visual/coverage/cooperative-pages.json"), "utf8"));
const baselineRoot = resolve("tests/visual/baselines");
const expected = new Set();
const duplicateNames = [];
const registryNames = new Set();
for (const entry of registry.entries) {
    if (!entry.visual) continue;
    const name = `${entry.module}--${entry.screen}--${entry.state}.png`;
    if (registryNames.has(name)) duplicateNames.push(name);
    registryNames.add(name);
    for (const project of entry.viewport_policy) expected.add(`${project}/${name}`);
}
const actual = new Set();
for (const project of ["desktop", "tablet", "mobile"]) {
    const directory = resolve(baselineRoot, project);
    for (const file of readdirSync(directory, { withFileTypes: true })) {
        if (file.isFile() && file.name.endsWith(".png")) actual.add(`${project}/${file.name}`);
    }
}
const missing = [...expected].filter((file) => !actual.has(file));
const orphan = [...actual].filter((file) => !expected.has(file));
const invalidDimensions = [];
for (const file of actual) {
    const fullPath = resolve(baselineRoot, file);
    if (!statSync(fullPath).isFile()) continue;
    const bytes = readFileSync(fullPath);
    const width = bytes.readUInt32BE(16);
    const height = bytes.readUInt32BE(20);
    const project = file.split("/", 1)[0];
    const expectedSize = { desktop: [1440, 900], tablet: [768, 1024], mobile: [390, 844] }[project];
    if (!expectedSize || width <= 0 || height <= 0) invalidDimensions.push(file);
}
console.log(JSON.stringify({ missing_baselines: missing.length, orphan_baselines: orphan.length, duplicate_names: duplicateNames.length, invalid_dimensions: invalidDimensions.length }, null, 2));
if (missing.length || orphan.length || duplicateNames.length || invalidDimensions.length) {
    console.error(JSON.stringify({ missing, orphan, duplicateNames, invalidDimensions }, null, 2));
    process.exit(1);
}
