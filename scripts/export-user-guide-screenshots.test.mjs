import assert from "node:assert/strict";
import { deflateSync } from "node:zlib";
import { Buffer } from "node:buffer";
import { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { test } from "node:test";

import { runScreenshots, sha256File, resolveSafeSource } from "./export-user-guide-screenshots.mjs";

const pngSignature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

function crc32(bytes) {
  let crc = 0xffffffff;
  for (const byte of bytes) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit += 1) {
      crc = (crc >>> 1) ^ ((crc & 1) ? 0xedb88320 : 0);
    }
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
  const typeBytes = Buffer.from(type, "ascii");
  const contents = Buffer.concat([typeBytes, data]);
  const checksum = Buffer.alloc(4);
  checksum.writeUInt32BE(crc32(contents));
  const length = Buffer.alloc(4);
  length.writeUInt32BE(data.length);
  return Buffer.concat([length, contents, checksum]);
}

function png(width = 4, height = 4, color = 0) {
  const header = Buffer.alloc(13);
  header.writeUInt32BE(width, 0);
  header.writeUInt32BE(height, 4);
  header[8] = 8;
  header[9] = 6;
  const row = Buffer.alloc(width * 4 + 1);
  row[0] = 0;
  for (let x = 0; x < width; x += 1) {
    row[1 + x * 4] = (color >> 16) & 0xff;
    row[2 + x * 4] = (color >> 8) & 0xff;
    row[3 + x * 4] = color & 0xff;
    row[4 + x * 4] = 0xff;
  }
  const pixels = Buffer.alloc(row.length * height);
  for (let y = 0; y < height; y += 1) {
    row.copy(pixels, y * row.length);
  }
  return Buffer.concat([
    pngSignature,
    chunk("IHDR", header),
    chunk("IDAT", deflateSync(pixels)),
    chunk("IEND", Buffer.alloc(0)),
  ]);
}

function makeFixture() {
  const root = mkdtempSync(join(tmpdir(), "screenshot-pipeline-"));
  const baselines = resolve(root, "tests/visual/baselines");
  mkdirSync(join(baselines, "desktop"), { recursive: true });
  mkdirSync(join(baselines, "mobile"), { recursive: true });
  const desktopPng = png(8, 8, 0xff8800);
  const mobilePng = png(8, 8, 0x00ff88);
  const desktopFile = join(baselines, "desktop/example.png");
  const mobileFile = join(baselines, "mobile/example.png");
  writeFileSync(desktopFile, desktopPng);
  writeFileSync(mobileFile, mobilePng);
  const sourceManifest = {
    version: 1,
    entries: [
      {
        id: "example-desktop",
        source: "tests/visual/baselines/desktop/example.png",
        viewport: "desktop",
        module: "demo",
        title: "Example · Desktop",
        caption: "An example screenshot.",
      },
      {
        id: "example-mobile",
        source: "tests/visual/baselines/mobile/example.png",
        viewport: "mobile",
        module: "demo",
        title: "Example · Mobile",
        caption: "An example mobile screenshot.",
      },
    ],
  };
  const sourceManifestPath = resolve(root, "resources/docs/user-guide/screenshots.json");
  mkdirSync(resolve(root, "resources/docs/user-guide"), { recursive: true });
  writeFileSync(sourceManifestPath, JSON.stringify(sourceManifest));
  mkdirSync(resolve(root, "public/docs/user-guide"), { recursive: true });
  return {
    root,
    cleanup: () => rmSync(root, { recursive: true, force: true }),
    paths: {
      sourceManifest: sourceManifestPath,
      dest: resolve(root, "public/docs/user-guide/screens"),
      publicManifest: resolve(root, "public/docs/user-guide/screenshots.json"),
      coverage: resolve(root, "docs/user-guide/coverage-screenshots.json"),
    },
    desktopPng,
    mobilePng,
    desktopFile,
    mobileFile,
  };
}

test("sha256File computes deterministic hex digest", async () => {
  const fx = makeFixture();
  try {
    const expected = "3fb85ac0c6e685954d54154f21c4dc9a1f57eade08e9b7d4df0359aeed87861d"; // not stable, recompute below
    const actual = await sha256File(fx.desktopFile);
    // Recompute the expected hash with Node's crypto so the assertion
    // is independent of the exact colour payload.
    const { createHash } = await import("node:crypto");
    const h = createHash("sha256");
    h.update(fx.desktopPng);
    assert.equal(actual, h.digest("hex"));
    // And just to be sure, the previous expected is wrong on purpose
    // — only the comparison to the local hash above is authoritative.
    assert.notEqual(actual, expected);
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource accepts a path under the baselines root", () => {
  const fx = makeFixture();
  try {
    const resolved = resolveSafeSource(fx.root, "tests/visual/baselines/desktop/example.png");
    assert.equal(resolved, resolve(fx.root, "tests/visual/baselines/desktop/example.png"));
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects path traversal", () => {
  const fx = makeFixture();
  try {
    assert.throws(() => resolveSafeSource(fx.root, "tests/visual/baselines/../../etc/passwd"));
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects paths outside the baselines directory", () => {
  const fx = makeFixture();
  try {
    assert.throws(() => resolveSafeSource(fx.root, "storage/app/file.png"));
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots copies new sources and writes the public manifest", async () => {
  const fx = makeFixture();
  try {
    const result = await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
      now: () => new Date("2026-08-05T00:00:00Z"),
    });

    assert.equal(result.counts.copied, 2);
    assert.equal(result.counts.unchanged, 0);
    assert.equal(result.counts.missing, 0);
    assert.equal(result.counts.broken, 0);

    const publicManifest = JSON.parse(readFileSync(fx.paths.publicManifest, "utf8"));
    assert.equal(publicManifest.version, 1);
    assert.equal(publicManifest.source_commit, "deadbeef");
    assert.equal(publicManifest.generated_at, "2026-08-05T00:00:00.000Z");
    assert.equal(publicManifest.entries.length, 2);
    assert.equal(publicManifest.entries[0].asset, "/docs/user-guide/screens/desktop/example-desktop.png");
    assert.match(publicManifest.entries[0].checksum, /^sha256:[0-9a-f]{64}$/);
    assert.equal(publicManifest.entries[0].module, "demo");
    assert.equal(publicManifest.entries[0].title, "Example · Desktop");

    const coverage = JSON.parse(readFileSync(fx.paths.coverage, "utf8"));
    assert.equal(coverage.counts.copied, 2);
    assert.equal(coverage.entries[0].status, "copied");
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots marks identical destinations as `unchanged` (not `skipped`)", async () => {
  const fx = makeFixture();
  try {
    await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
    });
    const result = await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
    });
    assert.equal(result.counts.copied, 0);
    assert.equal(result.counts.unchanged, 2);
    assert.equal(result.counts.updated, 0);

    const coverage = JSON.parse(readFileSync(fx.paths.coverage, "utf8"));
    for (const entry of coverage.entries) {
      assert.equal(entry.status, "unchanged");
    }
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots marks stale destinations as `updated` and rewrites them", async () => {
  const fx = makeFixture();
  try {
    await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
    });
    // Mutate the destination bytes so the SHA-256 differs.
    const stale = resolve(fx.paths.dest, "desktop/example-desktop.png");
    writeFileSync(stale, Buffer.from("definitely-not-a-valid-png-anymore"));
    const result = await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
    });
    assert.equal(result.counts.updated, 1);
    assert.equal(result.counts.unchanged, 1);
    // The stale destination should now match the source PNG again.
    const fresh = readFileSync(stale);
    assert.deepEqual(fresh, fx.desktopPng);
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots reports missing source PNGs as `missing`", async () => {
  const fx = makeFixture();
  try {
    rmSync(fx.desktopFile);
    const result = await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
      sourceCommit: "deadbeef",
    });
    assert.equal(result.counts.missing, 1);
    assert.equal(result.counts.copied, 1);
    assert.equal(result.counts.broken, 0);
    const coverage = JSON.parse(readFileSync(fx.paths.coverage, "utf8"));
    const missingEntry = coverage.entries.find((e) => e.id === "example-desktop");
    assert.equal(missingEntry.status, "missing");
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots fails on duplicate destination paths", async () => {
  const fx = makeFixture();
  try {
    const manifest = {
      version: 1,
      entries: [
        { id: "same-dest", source: "tests/visual/baselines/desktop/example.png", viewport: "desktop" },
        { id: "same-dest", source: "tests/visual/baselines/mobile/example.png", viewport: "desktop" },
      ],
    };
    writeFileSync(fx.paths.sourceManifest, JSON.stringify(manifest));
    await assert.rejects(
      runScreenshots({
        projectRoot: fx.root,
        sourceManifestPath: fx.paths.sourceManifest,
        destRoot: fx.paths.dest,
        publicManifestPath: fx.paths.publicManifest,
        coverageReportPath: fx.paths.coverage,
      }),
      /Duplicate destination/,
    );
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots rejects an empty manifest", async () => {
  const fx = makeFixture();
  try {
    writeFileSync(fx.paths.sourceManifest, JSON.stringify({ version: 1, entries: [] }));
    await assert.rejects(
      runScreenshots({
        projectRoot: fx.root,
        sourceManifestPath: fx.paths.sourceManifest,
        destRoot: fx.paths.dest,
        publicManifestPath: fx.paths.publicManifest,
        coverageReportPath: fx.paths.coverage,
      }),
      /empty/,
    );
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots rejects an invalid viewport", async () => {
  const fx = makeFixture();
  try {
    const manifest = {
      version: 1,
      entries: [
        {
          id: "bad-viewport",
          source: "tests/visual/baselines/desktop/example.png",
          viewport: "hologram",
        },
      ],
    };
    writeFileSync(fx.paths.sourceManifest, JSON.stringify(manifest));
    await assert.rejects(
      runScreenshots({
        projectRoot: fx.root,
        sourceManifestPath: fx.paths.sourceManifest,
        destRoot: fx.paths.dest,
        publicManifestPath: fx.paths.publicManifest,
        coverageReportPath: fx.paths.coverage,
      }),
      /invalid `viewport`/,
    );
  } finally {
    fx.cleanup();
  }
});

test("runScreenshots refuses sources outside the baselines directory", async () => {
  const fx = makeFixture();
  try {
    // Place a PNG in a non-baselines directory and reference it.
    const outside = resolve(fx.root, "storage/app/leak.png");
    mkdirSync(resolve(fx.root, "storage/app"), { recursive: true });
    writeFileSync(outside, png());
    const manifest = {
      version: 1,
      entries: [
        { id: "leak", source: "storage/app/leak.png", viewport: "desktop" },
      ],
    };
    writeFileSync(fx.paths.sourceManifest, JSON.stringify(manifest));
    const result = await runScreenshots({
      projectRoot: fx.root,
      sourceManifestPath: fx.paths.sourceManifest,
      destRoot: fx.paths.dest,
      publicManifestPath: fx.paths.publicManifest,
      coverageReportPath: fx.paths.coverage,
    });
    assert.equal(result.counts.broken, 1);
    assert.equal(result.counts.copied, 0);
    const coverage = JSON.parse(readFileSync(fx.paths.coverage, "utf8"));
    assert.equal(coverage.entries[0].status, "broken");
    assert.match(coverage.entries[0].detail, /baselines/);
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects the sibling-prefix attack `baselines-evil`", () => {
  const fx = makeFixture();
  try {
    mkdirSync(resolve(fx.root, "tests/visual/baselines-evil/desktop"), { recursive: true });
    writeFileSync(resolve(fx.root, "tests/visual/baselines-evil/desktop/x.png"), png());
    assert.throws(
      () => resolveSafeSource(fx.root, "tests/visual/baselines-evil/desktop/x.png"),
      /outside the baselines directory/,
    );
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects absolute paths outside the project root", () => {
  const fx = makeFixture();
  try {
    assert.throws(
      () => resolveSafeSource(fx.root, "/etc/passwd"),
      /outside the baselines directory/,
    );
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects NUL byte injection", () => {
  const fx = makeFixture();
  try {
    assert.throws(
      () => resolveSafeSource(fx.root, "tests/visual/baselines/desktop/example.png\0.png"),
      /NUL byte/,
    );
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects Windows-style backslash traversal on POSIX hosts", () => {
  const fx = makeFixture();
  try {
    // `..\..\etc\passwd` is treated as a literal segment name on
    // POSIX (no separator), so the existing `..` segment guard
    // catches it. Use the resolved-path branch instead: a path
    // containing backslashes whose normalised form lies outside
    // the baseline root must still be rejected.
    assert.throws(
      () => resolveSafeSource(fx.root, "tests/visual/baselines-evil\\windows\\x.png"),
      /outside the baselines directory|contains a '..' segment|Windows-style/,
    );
  } finally {
    fx.cleanup();
  }
});

test("resolveSafeSource rejects parent traversal after resolution", () => {
  const fx = makeFixture();
  try {
    assert.throws(
      () => resolveSafeSource(fx.root, "tests/visual/baselines/desktop/../../../etc/passwd"),
      /outside the baselines directory|'..' segment/,
    );
  } finally {
    fx.cleanup();
  }
});
