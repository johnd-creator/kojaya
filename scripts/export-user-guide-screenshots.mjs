#!/usr/bin/env node
/**
 * Copy user-guide screenshots from `tests/visual/baselines/` into
 * `public/docs/user-guide/screens/` so they can be served as static
 * assets by the in-app documentation center.
 *
 * Rules (from the in-app user guide contract):
 *  - Source must be a PNG file under `tests/visual/baselines/`.
 *  - The pipeline must NEVER modify a baseline; it only reads.
 *  - Source must be checksummed; the destination must NOT already
 *    exist (overwrite rejected).
 *  - Each screenshot in the manifest must have a corresponding
 *    baseline file (otherwise the script fails).
 *  - No production data is touched.
 */

import { existsSync, mkdirSync, readFileSync, statSync, writeFileSync, createReadStream } from "node:fs";
import { createHash } from "node:crypto";
import { readdir } from "node:fs/promises";
import { dirname, join, relative, resolve, sep } from "node:path";
import { fileURLToPath } from "node:url";
import { execSync } from "node:child_process";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, "..");
const manifestPath = resolve(projectRoot, "resources/docs/user-guide/screenshots.json");
const destDir = resolve(projectRoot, "public/docs/user-guide/screens");
const baselinesRoot = resolve(projectRoot, "tests/visual/baselines");

const fail = (message, detail) => {
  console.error(`docs:screenshots: ${message}`);
  if (detail) {
    console.error(detail);
  }
  process.exit(1);
};

const info = (message) => {
  console.log(`docs:screenshots: ${message}`);
};

if (!existsSync(manifestPath)) {
  fail(`Manifest not found: ${manifestPath}`);
}

let manifest;
try {
  manifest = JSON.parse(readFileSync(manifestPath, "utf8"));
} catch (error) {
  fail(`Cannot parse manifest: ${error.message}`);
}

if (!Array.isArray(manifest?.entries) || manifest.entries.length === 0) {
  fail("Manifest is empty; refusing to run silently.");
}

const seenDestinations = new Map();
let copied = 0;
let skipped = 0;

for (const entry of manifest.entries) {
  if (typeof entry.id !== "string" || entry.id === "") {
    fail("Manifest entry missing `id`.", JSON.stringify(entry));
  }
  if (typeof entry.source !== "string" || entry.source === "") {
    fail(`Entry ${entry.id} missing \`source\`.`);
  }
  if (typeof entry.viewport !== "string" || !["desktop", "tablet", "mobile"].includes(entry.viewport)) {
    fail(`Entry ${entry.id} has invalid \`viewport\` (${entry.viewport}).`);
  }

  // Source must live under tests/visual/baselines and be a PNG.
  const sourceAbsolute = resolve(projectRoot, entry.source);
  const baselinesPrefix = baselinesRoot + sep;
  if (!(sourceAbsolute + sep).startsWith(baselinesPrefix)) {
    fail(`Entry ${entry.id} source is outside the baselines directory: ${entry.source}`);
  }
  if (!existsSync(sourceAbsolute)) {
    fail(`Entry ${entry.id} source file is missing: ${entry.source}`);
  }
  const stat = statSync(sourceAbsolute);
  if (!stat.isFile()) {
    fail(`Entry ${entry.id} source is not a regular file: ${entry.source}`);
  }
  if (!sourceAbsolute.toLowerCase().endsWith(".png")) {
    fail(`Entry ${entry.id} source is not a PNG file: ${entry.source}`);
  }

  // Compute SHA-256 of the source for the manifest checksum column.
  const hash = createHash("sha256");
  await new Promise((resolveStream, rejectStream) => {
    const stream = createReadStream(sourceAbsolute);
    stream.on("data", (chunk) => hash.update(chunk));
    stream.on("end", resolveStream);
    stream.on("error", rejectStream);
  });
  const checksum = hash.digest("hex");

  // Destination path is keyed by entry id, with viewport subfolder.
  const destRelative = join(entry.viewport, `${entry.id}.png`);
  const destAbsolute = join(destDir, destRelative);
  const destProjectRelative = relative(projectRoot, destAbsolute);

  if (seenDestinations.has(destRelative)) {
    fail(
      `Duplicate destination for entries ${seenDestinations.get(destRelative)} and ${entry.id}: ${destProjectRelative}`,
    );
  }
  seenDestinations.set(destRelative, entry.id);

  if (existsSync(destAbsolute)) {
    info(`Skipping existing ${destProjectRelative}`);
    skipped += 1;
    continue;
  }

  mkdirSync(dirname(destAbsolute), { recursive: true });
  const bytes = readFileSync(sourceAbsolute);
  writeFileSync(destAbsolute, bytes);
  copied += 1;
  info(`Copied ${entry.id} → ${destProjectRelative} (sha256=${checksum.slice(0, 12)}…)`);
}

// Write a coverage report so CI and humans can see what was processed.
const reportPath = resolve(projectRoot, "docs/user-guide/coverage-screenshots.json");
let sourceCommit = "unknown";
try {
  sourceCommit = execSync("git rev-parse HEAD", { cwd: projectRoot, stdio: ["ignore", "pipe", "ignore"] })
    .toString()
    .trim();
} catch (error) {
  // Not a git checkout (e.g. packaged build) — fall back to manifest-only report.
}

const report = {
  generated_at: new Date().toISOString(),
  source_commit: sourceCommit,
  copied,
  skipped,
  total: manifest.entries.length,
  entries: manifest.entries.map((entry) => ({
    id: entry.id,
    source: entry.source,
    viewport: entry.viewport,
    destination: relative(projectRoot, join(destDir, entry.viewport, `${entry.id}.png`)),
  })),
};
mkdirSync(dirname(reportPath), { recursive: true });
writeFileSync(reportPath, JSON.stringify(report, null, 2) + "\n", "utf8");
info(`Coverage report written to ${relative(projectRoot, reportPath)}`);

// Also list any orphans (baselines in tests/visual/baselines that are
// not referenced) so the maintainer can decide whether to add them.
const baselineFiles = new Set();
for (const viewport of ["desktop", "tablet", "mobile"]) {
  const dir = join(baselinesRoot, viewport);
  if (!existsSync(dir)) {
    continue;
  }
  const files = await readdir(dir);
  for (const file of files) {
    if (file.toLowerCase().endsWith(".png")) {
      baselineFiles.add(relative(projectRoot, join(dir, file)));
    }
  }
}
const referenced = new Set(manifest.entries.map((entry) => entry.source));
const orphans = [...baselineFiles].filter((file) => !referenced.has(file));
info(`Orphaned baselines (not referenced by the user guide): ${orphans.length}`);
if (orphans.length > 0) {
  for (const file of orphans.slice(0, 20)) {
    info(`  orphan: ${file}`);
  }
  if (orphans.length > 20) {
    info(`  …and ${orphans.length - 20} more`);
  }
}

info(`Done. Copied=${copied} Skipped=${skipped} Total=${manifest.entries.length}`);
