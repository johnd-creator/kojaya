#!/usr/bin/env node
/**
 * Copy user-guide screenshots from `tests/visual/baselines/` into
 * `public/docs/user-guide/screens/` so they can be served as static
 * assets by the in-app documentation center, and emit both a public
 * runtime manifest and an internal coverage report.
 *
 * Contract (Fase 4 of the correction pass):
 *
 *  1. The source manifest is `resources/docs/user-guide/screenshots.json`.
 *  2. Every entry's `source` must live under `tests/visual/baselines/`,
 *     be a real PNG, and exist on disk.
 *  3. The script computes a SHA-256 of the source PNG. The destination
 *     file is re-copied whenever its SHA-256 does NOT match the source.
 *     Identical destination + source SHA-256 → `unchanged` (NOT
 *     `skipped`, because we actually verified the bytes).
 *  4. Two different entries may never resolve to the same destination
 *     path → the script fails.
 *  5. The script must NOT modify any baseline file.
 *  6. No production data is touched. The pipeline only reads PNGs and
 *     writes to `public/docs/user-guide/...` and to
 *     `docs/user-guide/coverage-screenshots.json`.
 *  7. The public manifest is written to
 *     `public/docs/user-guide/screenshots.json` with the shape:
 *
 *         {
 *           "version": 1,
 *           "source_commit": "<SHA>",
 *           "generated_at": "<ISO-8601>",
 *           "entries": [
 *             { "id": "...", "source": "...", "asset": "/docs/user-guide/screens/...",
 *               "viewport": "...", "module": "...", "title": "...", "caption": "...",
 *               "checksum": "sha256:<hex>" }
 *           ]
 *         }
 *
 *  8. The internal coverage report at
 *     `docs/user-guide/coverage-screenshots.json` tracks per-entry
 *     status as one of: `copied`, `updated`, `unchanged`, `missing`,
 *     `broken`. These categories are what the Fase 12 validator
 *     consumes.
 *
 * The script is structured as a module that exports `runScreenshots(opts)`
 * so the Node tests can exercise each branch without touching the
 * production manifest or baseline directory.
 */

import { existsSync, mkdirSync, readFileSync, statSync, writeFileSync, createReadStream } from "node:fs";
import { readdir } from "node:fs/promises";
import { createHash } from "node:crypto";
import { execSync } from "node:child_process";
import { dirname, isAbsolute, join, relative, resolve, sep } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, "..");

const VALID_VIEWPORTS = ["desktop", "tablet", "mobile"];
const ALLOWED_SOURCE_PREFIX = "tests/visual/baselines" + sep;
const PUBLIC_BASE = "public/docs/user-guide";
const SOURCE_MANIFEST = "resources/docs/user-guide/screenshots.json";
const PUBLIC_MANIFEST = "public/docs/user-guide/screenshots.json";
const PUBLIC_SCREEN_DIR = "public/docs/user-guide/screens";
const COVERAGE_REPORT = "docs/user-guide/coverage-screenshots.json";

const VALID_STATUSES = ["copied", "updated", "unchanged", "missing", "broken"];

/**
 * Compute SHA-256 of a file as hex.
 *
 * @param {string} path
 * @returns {Promise<string>}
 */
export async function sha256File(path) {
  const hash = createHash("sha256");
  await new Promise((resolveStream, rejectStream) => {
    const stream = createReadStream(path);
    stream.on("data", (chunk) => hash.update(chunk));
    stream.on("end", resolveStream);
    stream.on("error", rejectStream);
  });
  return hash.digest("hex");
}

/**
 * Strip an absolute path of any traversal attempt and verify that
 * the source lives under the baselines root.
 *
 * @param {string} projectRoot
 * @param {string} source
 * @returns {string} absolute, normalised path
 */
export function resolveSafeSource(projectRoot, source) {
  if (typeof source !== "string" || source.trim() === "") {
    throw new Error("Source is empty");
  }
  if (source.includes("\0")) {
    throw new Error("Source contains a NUL byte");
  }
  // Reject path-traversal tokens before resolution.
  if (source.split(/[\\/]+/).includes("..")) {
    throw new Error(`Source contains a '..' segment: ${source}`);
  }
  const absolute = isAbsolute(source) ? source : resolve(projectRoot, source);
  const normalised = resolve(absolute);
  const allowed = resolve(projectRoot, ALLOWED_SOURCE_PREFIX);
  if (!(normalised + sep).startsWith(allowed)) {
    throw new Error(`Source is outside the baselines directory: ${source}`);
  }
  return normalised;
}

/**
 * Run the screenshot export pipeline. The function is pure with
 * respect to the production baseline directory: it only ever reads
 * from it and writes to a destination rooted at `destRoot`.
 *
 * @param {{
 *   projectRoot?: string,
 *   sourceManifestPath?: string,
 *   destRoot?: string,
 *   publicManifestPath?: string,
 *   coverageReportPath?: string,
 *   sourceCommit?: string|null,
 *   now?: () => Date,
 * }} [opts]
 * @returns {Promise<{
 *   counts: Record<'copied'|'updated'|'unchanged'|'missing'|'broken', number>,
 *   entries: Array<object>,
 *   publicManifest: object,
 * }>}
 */
export async function runScreenshots(opts = {}) {
  const root = opts.projectRoot ?? projectRoot;
  const sourceManifestPath = opts.sourceManifestPath ?? resolve(root, SOURCE_MANIFEST);
  const destRoot = opts.destRoot ?? resolve(root, PUBLIC_SCREEN_DIR);
  const publicManifestPath = opts.publicManifestPath ?? resolve(root, PUBLIC_MANIFEST);
  const coverageReportPath = opts.coverageReportPath ?? resolve(root, COVERAGE_REPORT);
  const now = opts.now ?? (() => new Date());
  const sourceCommit = opts.sourceCommit ?? readHeadCommit(root);

  if (!existsSync(sourceManifestPath)) {
    throw new Error(`Source manifest not found: ${relative(root, sourceManifestPath)}`);
  }

  let manifest;
  try {
    manifest = JSON.parse(readFileSync(sourceManifestPath, "utf8"));
  } catch (error) {
    throw new Error(`Cannot parse source manifest: ${error.message}`);
  }

  if (!manifest || !Array.isArray(manifest.entries) || manifest.entries.length === 0) {
    throw new Error("Source manifest is empty; refusing to run silently.");
  }

  const seenDestinations = new Map();
  const reportEntries = [];
  const publicEntries = [];
  const counts = {
    copied: 0,
    updated: 0,
    unchanged: 0,
    missing: 0,
    broken: 0,
  };

  for (const entry of manifest.entries) {
    const id = entry.id;
    if (typeof id !== "string" || id === "") {
      throw new Error("Manifest entry missing `id`.");
    }
    if (typeof entry.source !== "string" || entry.source === "") {
      throw new Error(`Entry ${id} missing \`source\`.`);
    }
    if (typeof entry.viewport !== "string" || !VALID_VIEWPORTS.includes(entry.viewport)) {
      throw new Error(`Entry ${id} has invalid \`viewport\` (${entry.viewport}).`);
    }

    let sourceAbsolute;
    try {
      sourceAbsolute = resolveSafeSource(root, entry.source);
    } catch (error) {
      pushReport(reportEntries, publicEntries, {
        id,
        source: entry.source,
        viewport: entry.viewport,
        status: "broken",
        detail: error.message,
      });
      counts.broken += 1;
      continue;
    }

    if (!existsSync(sourceAbsolute)) {
      pushReport(reportEntries, publicEntries, {
        id,
        source: relative(root, sourceAbsolute),
        viewport: entry.viewport,
        status: "missing",
        detail: "Source PNG not found on disk.",
      });
      counts.missing += 1;
      continue;
    }

    const stat = statSync(sourceAbsolute);
    if (!stat.isFile() || !sourceAbsolute.toLowerCase().endsWith(".png")) {
      pushReport(reportEntries, publicEntries, {
        id,
        source: relative(root, sourceAbsolute),
        viewport: entry.viewport,
        status: "broken",
        detail: "Source is not a regular PNG file.",
      });
      counts.broken += 1;
      continue;
    }

    const destRelative = join(entry.viewport, `${id}.png`);
    if (seenDestinations.has(destRelative)) {
      throw new Error(
        `Duplicate destination for entries ${seenDestinations.get(destRelative)} and ${id}: ${destRelative}`,
      );
    }
    seenDestinations.set(destRelative, id);

    const destAbsolute = join(destRoot, destRelative);

    let sourceChecksum;
    try {
      sourceChecksum = await sha256File(sourceAbsolute);
    } catch (error) {
      pushReport(reportEntries, publicEntries, {
        id,
        source: relative(root, sourceAbsolute),
        viewport: entry.viewport,
        status: "broken",
        detail: `Cannot hash source: ${error.message}`,
      });
      counts.broken += 1;
      continue;
    }

    let status = "copied";
    if (existsSync(destAbsolute)) {
      try {
        const destChecksum = await sha256File(destAbsolute);
        status = destChecksum === sourceChecksum ? "unchanged" : "updated";
      } catch (error) {
        status = "updated";
      }
    }

    if (status !== "unchanged") {
      mkdirSync(dirname(destAbsolute), { recursive: true });
      writeFileSync(destAbsolute, readFileSync(sourceAbsolute));
    }

    counts[status] += 1;

    pushReport(reportEntries, publicEntries, {
      id,
      source: relative(root, sourceAbsolute),
      asset: "/" + relative(resolve(root, "public"), destAbsolute).split(sep).join("/"),
      viewport: entry.viewport,
      module: entry.module ?? null,
      title: entry.title ?? null,
      caption: entry.caption ?? null,
      checksum: `sha256:${sourceChecksum}`,
      status,
    });
  }

  const publicManifest = {
    version: 1,
    source_commit: sourceCommit,
    generated_at: now().toISOString(),
    entries: publicEntries,
  };

  const coverageReport = {
    version: 1,
    source_commit: sourceCommit,
    generated_at: publicManifest.generated_at,
    counts,
    total: manifest.entries.length,
    entries: reportEntries,
  };

  mkdirSync(dirname(publicManifestPath), { recursive: true });
  writeFileSync(publicManifestPath, JSON.stringify(publicManifest, null, 2) + "\n", "utf8");

  mkdirSync(dirname(coverageReportPath), { recursive: true });
  writeFileSync(coverageReportPath, JSON.stringify(coverageReport, null, 2) + "\n", "utf8");

  return { counts, entries: reportEntries, publicManifest };
}

function pushReport(reportEntries, publicEntries, row) {
  const publicRow = {
    id: row.id,
    source: row.source,
    asset: row.asset ?? null,
    viewport: row.viewport,
    module: row.module ?? null,
    title: row.title ?? null,
    caption: row.caption ?? null,
    checksum: row.checksum ?? null,
  };
  reportEntries.push({
    id: row.id,
    source: row.source,
    viewport: row.viewport,
    destination: row.asset ? "public" + row.asset : null,
    status: row.status,
    detail: row.detail ?? null,
  });
  publicEntries.push(publicRow);
}

function readHeadCommit(projectRoot) {
  try {
    return execSync("git rev-parse HEAD", { cwd: projectRoot, stdio: ["ignore", "pipe", "ignore"] })
      .toString()
      .trim();
  } catch (error) {
    return "unknown";
  }
}

function info(message) {
  console.log(`docs:screenshots: ${message}`);
}

// CLI entry point: when run as a script, surface a clear summary and
// a non-zero exit on any thrown error so the maintainer sees the
// failure rather than silent success.
if (import.meta.url === `file://${process.argv[1]}`) {
  runScreenshots()
    .then(({ counts, entries }) => {
      info(
        `copied=${counts.copied} updated=${counts.updated} unchanged=${counts.unchanged} missing=${counts.missing} broken=${counts.broken} total=${entries.length}`,
      );
      const problems = counts.missing + counts.broken;
      if (problems > 0) {
        process.exit(1);
      }
    })
    .catch((error) => {
      console.error(`docs:screenshots: ${error.message}`);
      process.exit(1);
    });
}
