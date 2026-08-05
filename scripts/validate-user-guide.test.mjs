import assert from "node:assert/strict";
import { execFileSync } from "node:child_process";
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { test } from "node:test";

/**
 * Build a fake project root with the minimum files the validator
 * needs to walk, then run the validator and capture its exit code
 * and stdout. The validator shells out to `php artisan route:list`
 * and `php -r '...PermissionEnum...'`; we cannot fake those easily
 * without booting a real Laravel, so the failing-fixture tests
 * focus on categories the validator can decide on its own:
 *
 *   - missing articles
 *   - duplicate slugs
 *   - invalid roles
 *   - empty body
 *   - body jargon
 *   - credentials
 *   - broken contextual-help mapping (unknown route)
 *
 * For the integration side (route / permission enumeration,
 * last_reviewed_commit ancestor check), the live repository's
 * `npm run docs:validate` already covers them.
 */

function runValidator(projectRoot) {
  const validatorPath = resolve(process.cwd(), "scripts/validate-user-guide.mjs");
  try {
    const out = execFileSync("node", [validatorPath], {
      cwd: projectRoot,
      encoding: "utf8",
      stdio: "pipe",
      env: {
        ...process.env,
        DOCS_VALIDATE_CWD: projectRoot,
      },
    });
    return { code: 0, stdout: out, stderr: "" };
  } catch (error) {
    return {
      code: error.status ?? 1,
      stdout: error.stdout?.toString() ?? "",
      stderr: error.stderr?.toString() ?? "",
    };
  }
}

function combinedOutput(result) {
  // The validator prints the summary on stdout and the per-error
  // messages on stderr. Most assertions care about either or
  // both, so we let them match against the union.
  return `${result.stdout}\n${result.stderr}`;
}

function makeFixture(setup) {
  const root = mkdtempSync(join(tmpdir(), "validator-fixture-"));
  mkdirSync(join(root, "docs/user-guide"), { recursive: true });
  mkdirSync(join(root, "resources/docs/user-guide"), { recursive: true });
  mkdirSync(join(root, "public/docs/user-guide/screens"), { recursive: true });
  setup(root);
  return {
    root,
    cleanup: () => rmSync(root, { recursive: true, force: true }),
  };
}

function writeArticle(root, name, body, overrides = {}) {
  const slug = overrides.slug ?? name;
  const fileName = overrides.fileName ?? `${slug}.md`;
  const file = join(root, "docs/user-guide", fileName);
  const data = {
    title: overrides.title ?? `Test ${name}`,
    slug,
    summary: overrides.summary ?? "Ringkasan artikel pengujian yang tidak mengandung jargon.",
    category: overrides.category ?? "Test",
    module: overrides.module ?? "test",
    roles: overrides.roles ?? ["all"],
    permissions: overrides.permissions ?? [],
    permission_mode: overrides.permission_mode ?? "all",
    route_names: overrides.route_names ?? [],
    risk_level: overrides.risk_level ?? "low",
    screenshot_entries: overrides.screenshot_entries ?? [],
    related_articles: overrides.related_articles ?? [],
    last_reviewed_commit: overrides.last_reviewed_commit ?? "20c86960",
    status: overrides.status ?? "published",
    sort_order: overrides.sort_order ?? 1,
  };
  const frontmatter = [
    "---",
    `title: ${data.title}`,
    `slug: ${data.slug}`,
    `summary: ${data.summary}`,
    `category: ${data.category}`,
    `module: ${data.module}`,
    `roles:`,
    ...data.roles.map((r) => `  - ${r}`),
    `permissions: []`,
    `permission_mode: ${data.permission_mode}`,
    `route_names: []`,
    `risk_level: ${data.risk_level}`,
    `screenshot_entries: []`,
    `related_articles: []`,
    `last_reviewed_commit: ${data.last_reviewed_commit}`,
    `status: ${data.status}`,
    `sort_order: ${data.sort_order}`,
    "---",
  ].join("\n");
  writeFileSync(file, `${frontmatter}\n\n${body}\n`);
  return file;
}

function writeContextualHelp(root, entries) {
  const path = join(root, "resources/docs/user-guide/contextual-help.json");
  writeFileSync(path, JSON.stringify({ version: 1, entries }, null, 2));
}

function writeInventory(root, slugs) {
  const path = join(root, "docs/user-guide/role-workflow-inventory.md");
  const body = [
    "# Inventory",
    "",
    ...slugs.map((s) => `- \`${s}\``),
  ].join("\n");
  writeFileSync(path, body);
}

function writeScreenshotManifest(root) {
  const path = join(root, "resources/docs/user-guide/screenshots.json");
  writeFileSync(path, JSON.stringify({ version: 1, entries: [] }, null, 2));
}

function writePublicScreenshotManifest(root, entries = []) {
  const path = join(root, "public/docs/user-guide/screenshots.json");
  writeFileSync(path, JSON.stringify({
    version: 1,
    source_commit: "20c86960",
    generated_at: new Date().toISOString(),
    entries,
  }, null, 2));
}

test("validator fails when there are no articles", () => {
  const fx = makeFixture((root) => {
    writeContextualHelp(root, []);
    writeInventory(root, []);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0, "Validator must fail with no articles.");
    assert.match(combinedOutput(result), /No Markdown files|missing required|errors=\d/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on duplicate slugs", () => {
  const fx = makeFixture((root) => {
    // Two different files (so the file system sees both) with
    // the SAME slug → the validator must catch the duplicate.
    writeArticle(root, "alpha-a", "## Tujuan\n\nIsi artikel pertama yang panjang dan valid untuk pengujian validator.", { slug: "alpha", fileName: "alpha-a.md" });
    writeArticle(root, "alpha-b", "## Tujuan\n\nIsi artikel kedua yang juga harus panjang agar tidak dianggap kosong dan valid.", { slug: "alpha", fileName: "alpha-b.md" });
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /Duplicate slug `alpha`/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on an invalid role in frontmatter", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", "## Tujuan\n\nIsi artikel yang panjang dan valid untuk pengujian validator pusat panduan.", {
      roles: ["supervisor_liar"],
    });
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /invalid role `supervisor_liar`/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails when an article body is too short", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", "## Tujuan\n\nPendek.");
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /body is too short/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a controller class name in the body", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", [
      "## Tujuan",
      "",
      "Anggota menggunakan antarmuka ini. Contoh baris:",
      "",
      "`MemberPortalController@dashboard` dipanggil saat login.",
      "",
      "Sisanya adalah teks penjelasan prosedur untuk anggota koperasi.",
    ].join("\n"));
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /controller class/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a route() helper call in the body", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", [
      "## Tujuan",
      "",
      "Langkah pertama adalah memanggil route('member.dashboard') untuk membuka portal anggota.",
      "",
      "Sisanya adalah teks penjelasan prosedur untuk anggota koperasi yang cukup panjang.",
    ].join("\n"));
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /route\(\) helper/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a Playwright account reference in the body", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", [
      "## Tujuan",
      "",
      "Akun pengujian: admin@playwright.com (dramatic-fennek).",
      "",
      "Sisanya adalah teks penjelasan prosedur untuk anggota koperasi yang cukup panjang.",
    ].join("\n"));
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /Playwright account/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a baseline path in the body", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", [
      "## Tujuan",
      "",
      "Lihat gambar di `tests/visual/baselines/desktop/example.png` untuk contoh.",
      "",
      "Sisanya adalah teks penjelasan prosedur untuk anggota koperasi yang cukup panjang.",
    ].join("\n"));
    writeContextualHelp(root, []);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /baseline file/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a contextual-help entry with an unknown slug", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", "## Tujuan\n\nIsi artikel pertama yang valid dan cukup panjang untuk pengujian.");
    writeContextualHelp(root, [
      {
        route: "member.dashboard",
        slug: "nonexistent-article",
        role: "anggota",
        screenshot_state: "default",
        label: "Portal",
      },
    ]);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /contextual-help\.json:.*unknown slug/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a duplicate (route, role) key in contextual-help.json", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", "## Tujuan\n\nIsi artikel pertama yang valid dan cukup panjang untuk pengujian.");
    writeContextualHelp(root, [
      {
        route: "member.dashboard",
        slug: "alpha",
        role: "anggota",
        screenshot_state: "default",
        label: "Portal",
      },
      {
        route: "member.dashboard",
        slug: "alpha",
        role: "anggota",
        screenshot_state: "default",
        label: "Portal Lagi",
      },
    ]);
    writeInventory(root, ["alpha"]);
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /duplicate \(route, role\) key/);
  } finally {
    fx.cleanup();
  }
});

test("validator fails on a published article that is missing from the inventory", () => {
  const fx = makeFixture((root) => {
    writeArticle(root, "alpha", "## Tujuan\n\nIsi artikel pertama yang valid dan cukup panjang untuk pengujian.");
    writeContextualHelp(root, []);
    writeInventory(root, []); // alpha is NOT listed
    writeScreenshotManifest(root);
    writePublicScreenshotManifest(root);
  });
  try {
    const result = runValidator(fx.root);
    assert.notEqual(result.code, 0);
    assert.match(combinedOutput(result), /is not referenced in/);
  } finally {
    fx.cleanup();
  }
});
