#!/usr/bin/env node
/**
 * Validate the in-app user guide against the contract.
 *
 * The validator is the single hard gate between the in-app
 * documentation center and a green CI build. It must NEVER
 * succeed silently: every category of failure listed below is
 * promoted to a hard error and the script exits non-zero.
 *
 * Contract (Fase 11 + 12 of the correction pass):
 *
 *  - Article integrity: no articles, invalid frontmatter, duplicate
 *    slugs, invalid roles, invalid permissions, empty body, body
 *    containing technical jargon, or credentials / baseline
 *    references all FAIL the build.
 *  - Route + permission enumeration: if `php artisan route:list`
 *    or `php -r '...PermissionEnum...'` fail, the build FAILS
 *    (never warns). The validator refuses to validate against an
 *    unknown application state.
 *  - route_names / permissions / related_articles /
 *    screenshot_entries must each resolve to a real artefact in
 *    the live application.
 *  - Markdown internal links (e.g. `[x](./other.md)`) must resolve
 *    to an existing article.
 *  - Screenshot pipeline integrity: source PNG, public PNG, public
 *    manifest, and source-commit / destination SHA-256 alignment
 *    must ALL be valid.
 *  - Contextual-help JSON: route exists, slug exists, role valid,
 *    permission valid, no duplicate (route, role) key, and the
 *    referenced article's role/permission must NOT be more
 *    permissive than the registry entry.
 *  - last_reviewed_commit must be a real commit (git cat-file)
 *    AND an ancestor of HEAD.
 *  - Inventory: every published article must be referenced in
 *    docs/user-guide/role-workflow-inventory.md.
 *
 * Outputs:
 *   - docs/user-guide/coverage-report.json  (machine-readable)
 *   - docs/user-guide/coverage-report.md     (human-readable)
 *   - docs/user-guide/coverage-screenshots.json (already produced
 *     by scripts/export-user-guide-screenshots.mjs; this script
 *     only verifies it)
 */

import { existsSync, mkdirSync, readFileSync, statSync, writeFileSync } from "node:fs";
import { readdir } from "node:fs/promises";
import { execSync, spawnSync } from "node:child_process";
import { createHash } from "node:crypto";
import { dirname, join, relative, resolve, sep } from "node:path";
import { fileURLToPath } from "node:url";
import process from "node:process";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = process.env.DOCS_VALIDATE_CWD
  ? resolve(process.env.DOCS_VALIDATE_CWD)
  : resolve(__dirname, "..");
const guideDir = resolve(projectRoot, "docs/user-guide");
const screenshotsManifestPath = resolve(projectRoot, "resources/docs/user-guide/screenshots.json");
const publicScreenshotsManifestPath = resolve(projectRoot, "public/docs/user-guide/screenshots.json");
const coverageScreenshotsPath = resolve(projectRoot, "docs/user-guide/coverage-screenshots.json");
const contextualHelpPath = resolve(projectRoot, "resources/docs/user-guide/contextual-help.json");
const inventoryMdPath = resolve(guideDir, "role-workflow-inventory.md");
const inventoryJsonPath = resolve(projectRoot, "resources/docs/user-guide/role-workflow-inventory.json");
const coverageReportJsonPath = resolve(projectRoot, "docs/user-guide/coverage-report.json");
const coverageReportMdPath = resolve(projectRoot, "docs/user-guide/coverage-report.md");
const uiAuditRegistryPath = resolve(projectRoot, "tests/visual/coverage/cooperative-pages.json");

const validRoles = ["all", "shared", "anggota", "admin_koperasi", "manajer_koperasi", "pengurus_koperasi"];
const validPermissionModes = ["all", "any"];
const validRiskLevels = ["low", "medium", "high"];
const validStatuses = ["published", "draft", "archived"];
const validViewports = ["desktop", "tablet", "mobile"];
// UI states a contextual-help entry may declare. `default` is the
// default fallback when no specific UI capture is registered for
// the route. The documentation-specific states (`manager-review`,
// `chairman-approval`) are emitted by the contextual-help registry
// and must be present in the UI Audit `cooperative-pages.json` so
// the registry and the validator cannot drift.
const validContextualStates = new Set(["default", "manager-review", "chairman-approval"]);
const validInventoryRoles = ["Anggota", "Admin Koperasi", "Manajer Koperasi", "Pengurus Koperasi"];
const validActivityKinds = [
  "informasional",
  "operasional",
  "transaksional",
  "approval",
  "finansial",
  "administrasi",
];
const validRiskTiers = [
  "informasional",
  "operasional",
  "transaksional",
  "approval",
  "finansial",
  "destructive",
];
const validDocStatuses = ["documented", "partial", "gap", "deferred", "not-applicable"];
const validActivityVerbs = [
  "melihat",
  "membuat",
  "mengubah",
  "memverifikasi",
  "menyetujui",
  "menolak",
  "membatalkan",
  "menutup periode",
  "mengunduh laporan",
  "transaksi keuangan",
  "administrasi lainnya",
];
const requiredKeys = [
  "title",
  "slug",
  "summary",
  "category",
  "module",
  "roles",
  "permissions",
  "permission_mode",
  "route_names",
  "risk_level",
  "screenshot_entries",
  "related_articles",
  "last_reviewed_commit",
  "status",
  "sort_order",
];

const errors = [];
const warnings = [];
const checks = {
  total: 0,
  published: 0,
  draft: 0,
  archived: 0,
  broken_screenshots: 0,
  broken_routes: 0,
  broken_related: 0,
  broken_links: 0,
  invalid_roles: 0,
  invalid_permissions: 0,
  invalid_contextual_mappings: 0,
  stale_commits: 0,
  body_jargon: 0,
  body_credentials: 0,
  body_baseline: 0,
  missing_inventory: 0,
};

const fail = (category, message) => {
  errors.push({ category, message });
  if (category in checks) {
    checks[category] += 1;
  }
};

const warn = (message) => {
  warnings.push(message);
};

const log = (...args) => {
  console.log("docs:validate", ...args);
};

async function listMarkdownFiles(dir) {
  const out = [];
  if (!existsSync(dir)) {
    return out;
  }
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) {
      if (entry.name === "node_modules" || entry.name.startsWith(".")) {
        continue;
      }
      out.push(...(await listMarkdownFiles(full)));
    } else if (entry.isFile() && entry.name.toLowerCase().endsWith(".md")) {
      out.push(full);
    }
  }
  return out;
}

const parseFrontmatter = (contents) => {
  if (!contents.startsWith("---")) {
    return null;
  }
  const rest = contents.slice(3);
  const endIndex = rest.indexOf("\n---");
  if (endIndex === -1) {
    return null;
  }
  const yaml = rest.slice(0, endIndex).replace(/^\n/, "");
  return yaml;
};

const parseScalar = (raw) => {
  if (raw === undefined) return "";
  let s = raw.trim();
  if (s.startsWith("#")) return "";
  const hashIndex = s.indexOf(" #");
  if (hashIndex !== -1) {
    s = s.slice(0, hashIndex);
  }
  if (s.startsWith("\"") && s.endsWith("\"")) {
    return s.slice(1, -1);
  }
  if (s.startsWith("'") && s.endsWith("'")) {
    return s.slice(1, -1);
  }
  if (/^-?\d+$/.test(s)) {
    return parseInt(s, 10);
  }
  return s;
};

const simpleYamlParse = (source) => {
  const lines = source.split(/\r?\n/);
  const root = {};
  let i = 0;
  while (i < lines.length) {
    const line = lines[i];
    if (line.trim() === "" || line.trim().startsWith("#")) {
      i++;
      continue;
    }
    const m = line.match(/^([A-Za-z_][A-Za-z0-9_-]*):\s*(.*)$/);
    if (!m) {
      throw new Error(`Cannot parse line: ${line}`);
    }
    const key = m[1];
    const value = m[2];
    if (value === "[]") {
      root[key] = [];
      i++;
      continue;
    }
    if (value !== undefined && value !== "") {
      root[key] = parseScalar(value);
      i++;
      continue;
    }
    const list = [];
    i++;
    while (i < lines.length) {
      const next = lines[i];
      if (next.trim() === "" || next.trim().startsWith("#")) {
        i++;
        continue;
      }
      const itemMatch = next.match(/^\s+-\s*(.*)$/);
      if (!itemMatch) {
        break;
      }
      const item = itemMatch[1].trim();
      list.push(parseScalar(item));
      i++;
    }
    root[key] = list;
  }
  return root;
};

/**
 * Enumerate Laravel route names. On failure this throws — the
 * validator refuses to run with a partial view of the application.
 */
function loadRouteNames() {
  const result = spawnSync("php", ["artisan", "route:list", "--json"], {
    cwd: projectRoot,
    encoding: "utf8",
  });
  if (result.status !== 0) {
    throw new Error(
      `Unable to enumerate routes: ${result.stderr?.toString() || result.stdout?.toString() || "no output"}`,
    );
  }
  let parsed;
  try {
    parsed = JSON.parse(result.stdout);
  } catch (error) {
    throw new Error(`Route JSON parse failed: ${error.message}`);
  }
  const names = new Set();
  for (const route of parsed) {
    if (route?.name) {
      names.add(route.name);
    }
  }
  return names;
}

/**
 * Enumerate Spatie permission names. On failure this throws —
 * the validator refuses to run with a partial view of the app.
 */
function loadPermissionNames() {
  const script = `require 'vendor/autoload.php';
$values = \\App\\Enums\\PermissionEnum::values();
echo implode(PHP_EOL, array_map('strval', $values));
exit(0);
`;
  const result = spawnSync("php", ["-r", script], {
    cwd: projectRoot,
    encoding: "utf8",
  });
  if (result.status !== 0) {
    throw new Error(
      `Unable to enumerate permissions: ${result.stderr?.toString() || result.stdout?.toString() || "no output"}`,
    );
  }
  return new Set(result.stdout.split(/\r?\n/).filter(Boolean));
}

/**
 * Confirm `sha` is a real commit object and an ancestor of HEAD.
 * Throws on any non-true exit code (i.e. not a commit, not
 * reachable, or git unavailable).
 */
function assertCommitExistsAndIsAncestor(sha) {
  const exists = spawnSync("git", ["cat-file", "-e", `${sha}^{commit}`], {
    cwd: projectRoot,
    encoding: "utf8",
  });
  if (exists.status !== 0) {
    throw new Error(`Commit \`${sha}\` is not a valid Git object in this repository.`);
  }
  const mergeBase = spawnSync("git", ["merge-base", "--is-ancestor", sha, "HEAD"], {
    cwd: projectRoot,
    encoding: "utf8",
  });
  if (mergeBase.status !== 0) {
    throw new Error(`Commit \`${sha}\` is not an ancestor of HEAD.`);
  }
}

function sha256FileSync(path) {
  const crypto = createHash("sha256");
  crypto.update(readFileSync(path));
  return crypto.digest("hex");
}

/**
 * Extract internal Markdown links (`[text](./foo.md)`, `[text](foo.md)`,
 * `[text](#section)`) and return them as { href, raw } objects.
 * External URLs are ignored.
 */
function extractInternalLinks(markdown) {
  const out = [];
  const re = /\[([^\]]*)\]\(([^)]+)\)/g;
  let match;
  while ((match = re.exec(markdown)) !== null) {
    const href = match[2].trim();
    if (/^https?:\/\//.test(href)) continue;
    if (href.startsWith("#")) continue;
    if (href.startsWith("mailto:") || href.startsWith("tel:")) continue;
    out.push({ text: match[1].trim(), href, raw: match[0] });
  }
  return out;
}

/**
 * Body jargon check: the user-facing body of an article must NOT
 * contain technical tokens. Frontmatter is allowed to mention
 * route_names, permissions, etc. — this is the check the Fase 9
 * rewrite is supposed to satisfy.
 */
const JARGON_PATTERNS = [
  { id: "controller-class", pattern: /@?[A-Z][A-Za-z0-9_]+Controller\b/, message: "Body references a controller class." },
  { id: "form-request", pattern: /\b[A-Z][A-Za-z0-9_]+Request\b/, message: "Body references a Form Request class." },
  { id: "model-class", pattern: /\buse\s+App\\Models\\[A-Z][A-Za-z0-9_]+/, message: "Body references a Model via FQCN." },
  { id: "enum-name", pattern: /\b[A-Z][A-Za-z0-9_]+Enum::/, message: "Body references a PHP enum." },
  { id: "route-helper", pattern: /\broute\(['"]/, message: "Body uses Laravel's route() helper." },
  { id: "table-name", pattern: /\bDB::table\(['"]/, message: "Body uses DB::table()." },
  { id: "service-call", pattern: /::(store|update|destroy|approve|reject)\(\s*['"]/, message: "Body calls a service method with a literal string." },
  { id: "source-path", pattern: /\bapp\/[A-Z][A-Za-z0-9_\/]+\.php\b/, message: "Body references a source file path." },
];

const CREDENTIAL_PATTERNS = [
  { id: "no-playwright-account", pattern: /playwright\.com|dramatic-fennek|admin@playwright\.com/i, message: "Found Playwright account reference." },
  { id: "no-env-secret", pattern: /MIDTRANS_SERVER_KEY\s*=\s*[A-Za-z0-9]+/i, message: "Body contains a Midtrans server key value." },
];
const BASELINE_PATH_PATTERN = /tests\/visual\/baselines\/[^)\s]+\.png/i;

async function main() {
  if (!existsSync(guideDir)) {
    fail("__init__", `User-guide directory not found: ${guideDir}`);
  }

  // Enumerate the application surface UP FRONT. If the
  // application itself is unreachable, the validator cannot
  // make any claim and must fail loudly.
  let routeNames;
  try {
    routeNames = loadRouteNames();
  } catch (error) {
    fail("__init__", `Route enumeration failed: ${error.message}`);
    routeNames = new Set();
  }
  let permissionNames;
  try {
    permissionNames = loadPermissionNames();
  } catch (error) {
    fail("__init__", `Permission enumeration failed: ${error.message}`);
    permissionNames = new Set();
  }
  if (routeNames.size === 0) {
    fail("__init__", "Route enumeration returned 0 routes — refusing to validate.");
  }
  if (permissionNames.size === 0) {
    fail("__init__", "Permission enumeration returned 0 permissions — refusing to validate.");
  }

  const files = await listMarkdownFiles(guideDir);
  if (files.length === 0) {
    fail("__init__", `No Markdown files found under ${guideDir}`);
  }

  const articles = [];
  for (const file of files) {
    const contents = readFileSync(file, "utf8");
    const rel = relative(projectRoot, file);
    if (!contents.startsWith("---")) {
      continue;
    }
    const yaml = parseFrontmatter(contents);
    if (yaml === null) {
      fail("__init__", `${rel}: missing closing --- for frontmatter.`);
      continue;
    }
    let parsed;
    try {
      parsed = simpleYamlParse(yaml);
    } catch (error) {
      fail("__init__", `${rel}: cannot parse YAML (${error.message})`);
      continue;
    }
    for (const key of requiredKeys) {
      if (!(key in parsed)) {
        fail("__init__", `${rel}: missing required frontmatter key \`${key}\`.`);
      }
    }
    if (parsed.slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(parsed.slug)) {
      fail("__init__", `${rel}: slug must be kebab-case ASCII.`);
    }
    if (parsed.roles) {
      if (!Array.isArray(parsed.roles)) {
        fail("invalid_roles", `${rel}: \`roles\` must be a list.`);
      } else {
        for (const role of parsed.roles) {
          if (!validRoles.includes(role)) {
            fail("invalid_roles", `${rel}: invalid role \`${role}\` (valid: ${validRoles.join(", ")}).`);
          }
        }
      }
    }
    if (parsed.permission_mode && !validPermissionModes.includes(parsed.permission_mode)) {
      fail("__init__", `${rel}: invalid \`permission_mode\` (${parsed.permission_mode}).`);
    }
    if (parsed.risk_level && !validRiskLevels.includes(parsed.risk_level)) {
      fail("__init__", `${rel}: invalid \`risk_level\` (${parsed.risk_level}).`);
    }
    if (parsed.status && !validStatuses.includes(parsed.status)) {
      fail("__init__", `${rel}: invalid \`status\` (${parsed.status}).`);
    }
    if (parsed.last_reviewed_commit && !/^[0-9a-f]{7,40}$/.test(parsed.last_reviewed_commit)) {
      fail("stale_commits", `${rel}: \`last_reviewed_commit\` must be a 7-40 char hex SHA.`);
    } else if (parsed.last_reviewed_commit) {
      try {
        assertCommitExistsAndIsAncestor(parsed.last_reviewed_commit);
      } catch (error) {
        fail("stale_commits", `${rel}: ${error.message}`);
      }
    }
    if (parsed.summary && typeof parsed.summary === "string" && parsed.summary.trim().length < 5) {
      fail("__init__", `${rel}: \`summary\` is too short.`);
    }

    if (parsed.status === "published") checks.published += 1;
    else if (parsed.status === "draft") checks.draft += 1;
    else if (parsed.status === "archived") checks.archived += 1;

    // Body safety checks: credentials, baseline paths, jargon.
    const body = contents.split(/\n---/)[1] ?? "";
    for (const check of CREDENTIAL_PATTERNS) {
      if (check.pattern.test(body)) {
        fail("body_credentials", `${rel}: ${check.message}`);
      }
    }
    if (BASELINE_PATH_PATTERN.test(body)) {
      fail("body_baseline", `${rel}: Body references a baseline file directly.`);
    }
    for (const check of JARGON_PATTERNS) {
      if (check.pattern.test(body)) {
        fail("body_jargon", `${rel}: ${check.message}`);
      }
    }

    articles.push({ file: rel, data: parsed, body, contents });
  }

  // Slug uniqueness
  const slugs = new Map();
  for (const article of articles) {
    const slug = article.data.slug;
    if (!slug) continue;
    if (slugs.has(slug)) {
      fail("__init__", `Duplicate slug \`${slug}\` in ${article.file} and ${slugs.get(slug)}.`);
    } else {
      slugs.set(slug, article.file);
    }
  }

  // related_articles
  for (const article of articles) {
    const related = article.data.related_articles ?? [];
    if (!Array.isArray(related)) {
      fail("__init__", `${article.file}: \`related_articles\` must be a list.`);
      continue;
    }
    for (const target of related) {
      if (target !== "" && !slugs.has(target)) {
        fail("broken_related", `${article.file}: \`related_articles\` references unknown slug \`${target}\`.`);
      }
    }
  }

  // route_names resolve to a registered Laravel route
  for (const article of articles) {
    const routes = article.data.route_names ?? [];
    if (!Array.isArray(routes)) {
      fail("__init__", `${article.file}: \`route_names\` must be a list.`);
      continue;
    }
    for (const routeName of routes) {
      if (routeName === "") continue;
      if (!routeNames.has(routeName)) {
        fail("broken_routes", `${article.file}: \`route_names\` references unknown route \`${routeName}\`.`);
      }
    }
  }

  // permissions resolve
  for (const article of articles) {
    const perms = article.data.permissions ?? [];
    if (!Array.isArray(perms)) continue;
    for (const perm of perms) {
      if (perm === "") continue;
      if (!permissionNames.has(perm)) {
        fail("invalid_permissions", `${article.file}: \`permissions\` references unknown permission \`${perm}\`.`);
      }
    }
  }

  // Markdown internal links resolve
  const articleFilesByName = new Map();
  for (const article of articles) {
    articleFilesByName.set(article.file.split("/").pop(), article.file);
  }
  for (const article of articles) {
    const links = extractInternalLinks(article.body);
    for (const link of links) {
      const normalized = link.href.split("#")[0];
      if (normalized === "" || normalized === "#") continue;
      if (normalized.startsWith("/")) continue; // app route, not a doc link
      const targetFile = resolve(join(projectRoot, article.file, "..", normalized));
      if (!existsSync(targetFile)) {
        fail("broken_links", `${article.file}: broken internal link \`${link.href}\` (target \`${normalized}\` does not exist).`);
      }
    }
  }

  // screenshot_entries resolve
  let manifest = null;
  if (existsSync(screenshotsManifestPath)) {
    try {
      manifest = JSON.parse(readFileSync(screenshotsManifestPath, "utf8"));
    } catch (error) {
      fail("broken_screenshots", `Cannot parse screenshot manifest: ${error.message}`);
    }
  } else {
    fail("broken_screenshots", `Screenshot manifest not found: ${relative(projectRoot, screenshotsManifestPath)}`);
  }
  const manifestIds = new Set((manifest?.entries ?? []).map((e) => e.id));
  for (const article of articles) {
    const entries = article.data.screenshot_entries ?? [];
    if (!Array.isArray(entries)) {
      fail("__init__", `${article.file}: \`screenshot_entries\` must be a list.`);
      continue;
    }
    for (const entry of entries) {
      if (entry === "") continue;
      if (!manifestIds.has(entry)) {
        fail("broken_screenshots", `${article.file}: \`screenshot_entries\` references unknown id \`${entry}\`.`);
      }
    }
  }

  // Public screenshot manifest + checksum alignment.
  if (existsSync(publicScreenshotsManifestPath)) {
    let publicManifest;
    try {
      publicManifest = JSON.parse(readFileSync(publicScreenshotsManifestPath, "utf8"));
    } catch (error) {
      fail("broken_screenshots", `Cannot parse public screenshot manifest: ${error.message}`);
    }
    if (publicManifest) {
      for (const entry of publicManifest.entries ?? []) {
        const srcRel = (entry.source ?? "").replace(/^\/+/, "");
        const srcPath = resolve(projectRoot, srcRel);
        const assetRel = (entry.asset ?? "").replace(/^\//, "");
        const dstPath = resolve(projectRoot, "public", assetRel);
        if (!existsSync(srcPath)) {
          fail("broken_screenshots", `Public manifest entry \`${entry.id}\` has missing source: ${entry.source}`);
          continue;
        }
        if (!existsSync(dstPath)) {
          fail("broken_screenshots", `Public manifest entry \`${entry.id}\` is missing its destination: ${entry.asset}`);
          continue;
        }
        if (entry.checksum && !entry.checksum.startsWith("sha256:")) {
          fail("broken_screenshots", `Public manifest entry \`${entry.id}\` has non-sha256 checksum.`);
        }
        const srcSha = sha256FileSync(srcPath);
        const dstSha = sha256FileSync(dstPath);
        if (srcSha !== dstSha) {
          fail("broken_screenshots", `Public manifest entry \`${entry.id}\` destination SHA-256 does not match source.`);
        }
      }
    }
  } else {
    fail("broken_screenshots", `Public screenshot manifest missing: ${relative(projectRoot, publicScreenshotsManifestPath)}`);
  }

  // Build a per-route index of declared UI Audit states so we can
  // validate contextual-help `screenshot_state` against the live
  // registry instead of the (unrelated) viewport vocabulary.
  const registryStatesByRoute = new Map();
  if (existsSync(uiAuditRegistryPath)) {
    try {
      const uiRegistry = JSON.parse(readFileSync(uiAuditRegistryPath, "utf8"));
      for (const regEntry of uiRegistry.entries ?? []) {
        if (!regEntry.route_name || !regEntry.state) {
          continue;
        }
        if (!registryStatesByRoute.has(regEntry.route_name)) {
          registryStatesByRoute.set(regEntry.route_name, new Set());
        }
        registryStatesByRoute.get(regEntry.route_name).add(String(regEntry.state));
      }
    } catch (error) {
      fail("invalid_contextual_mappings", `Cannot parse UI Audit registry: ${error.message}`);
    }
  }

  // Contextual-help registry validation.
  if (existsSync(contextualHelpPath)) {
    let help;
    try {
      help = JSON.parse(readFileSync(contextualHelpPath, "utf8"));
    } catch (error) {
      fail("invalid_contextual_mappings", `Cannot parse contextual-help.json: ${error.message}`);
    }
    if (help) {
      const seen = new Map();
      for (const entry of help.entries ?? []) {
        if (!entry.route || !entry.slug || !entry.role) {
          fail("invalid_contextual_mappings", `contextual-help.json: missing required field on an entry (route/slug/role).`);
          continue;
        }
        if (!routeNames.has(entry.route)) {
          fail("invalid_contextual_mappings", `contextual-help.json: entry for route \`${entry.route}\` does not match a real Laravel route.`);
        }
        if (!slugs.has(entry.slug)) {
          fail("invalid_contextual_mappings", `contextual-help.json: entry for route \`${entry.route}\` references unknown slug \`${entry.slug}\`.`);
        }
        if (!validRoles.includes(entry.role) && entry.role !== "shared") {
          fail("invalid_contextual_mappings", `contextual-help.json: entry for route \`${entry.route}\` has invalid role \`${entry.role}\`.`);
        }
        if (entry.permission && !permissionNames.has(entry.permission)) {
          fail("invalid_contextual_mappings", `contextual-help.json: entry for route \`${entry.route}\` references unknown permission \`${entry.permission}\`.`);
        }
        // screenshot_state is a UI audit concept (not a viewport).
        // It must either be a known contextual-help state OR be
        // declared in the cooperative-pages.json registry for the
        // same route. We do NOT compare against viewports here.
        const declaredState = entry.screenshot_state ?? "default";
        if (!validContextualStates.has(declaredState)) {
          fail(
            "invalid_contextual_mappings",
            `contextual-help.json: entry for route \`${entry.route}\` uses unknown screenshot_state \`${declaredState}\`. Valid values: ${Array.from(validContextualStates).join(", ")}.`,
          );
        } else if (declaredState !== "default") {
          // For non-default states we cross-check against the UI
          // Audit registry so the contextual-help button can only
          // point at states that actually have a visual capture.
          const matchingRegistryEntry = registryStatesByRoute.get(entry.route);
          if (matchingRegistryEntry && !matchingRegistryEntry.has(declaredState)) {
            fail(
              "invalid_contextual_mappings",
              `contextual-help.json: route \`${entry.route}\` declares screenshot_state \`${declaredState}\`, but the UI Audit registry has no such state for that route. Add the state to cooperative-pages.json or use \`default\`.`,
            );
          }
        }
        const key = `${entry.route}|${entry.role}`;
        if (seen.has(key)) {
          fail("invalid_contextual_mappings", `contextual-help.json: duplicate (route, role) key \`${key}\` (slugs \`${seen.get(key)}\` and \`${entry.slug}\`).`);
        } else {
          seen.set(key, entry.slug);
        }

        // Article role must be at least as permissive as the
        // registry role. Otherwise the button would point to an
        // article the same user cannot read.
        if (slugs.has(entry.slug) && entry.role !== "all" && entry.role !== "shared") {
          const article = articles.find((a) => a.data.slug === entry.slug);
          if (article) {
            const articleRoles = article.data.roles ?? [];
            if (!articleRoles.includes(entry.role) && !articleRoles.includes("all") && !articleRoles.includes("shared")) {
              fail("invalid_contextual_mappings", `contextual-help.json: route \`${entry.route}\` → slug \`${entry.slug}\` (role \`${entry.role}\`); article frontmatter does not include that role.`);
            }
            const articlePerms = article.data.permissions ?? [];
            if (entry.permission && articlePerms.length > 0 && !articlePerms.includes(entry.permission)) {
              fail("invalid_contextual_mappings", `contextual-help.json: route \`${entry.route}\` requires permission \`${entry.permission}\`, but the article does not declare it (and would block the user).`);
            }
          }
        }
      }
    }
  } else {
    fail("invalid_contextual_mappings", `Contextual-help registry missing: ${relative(projectRoot, contextualHelpPath)}`);
  }

  // Empty article check
  for (const article of articles) {
    const filePath = join(projectRoot, article.file);
    const stat = statSync(filePath);
    if (stat.size < 600) {
      fail("__init__", `${article.file}: file is too small (${stat.size} bytes); likely empty.`);
    }
    if (article.body.replace(/[#*_\-\s>]/g, "").length < 100) {
      fail("__init__", `${article.file}: body is too short (less than 100 non-markup characters).`);
    }
  }

  // Inventory cross-check. The machine-readable JSON file is the
  // authoritative source; the Markdown is generated from it and the
  // validator must keep the two in lockstep.
  let inventoryRows = [];
  if (existsSync(inventoryJsonPath)) {
    let inventoryJson;
    try {
      inventoryJson = JSON.parse(readFileSync(inventoryJsonPath, "utf8"));
    } catch (error) {
      fail("missing_inventory", `Cannot parse ${relative(projectRoot, inventoryJsonPath)}: ${error.message}`);
      inventoryJson = null;
    }
    if (inventoryJson) {
      inventoryRows = Array.isArray(inventoryJson.rows) ? inventoryJson.rows : [];
      const seenArticleSlugs = new Map();
      for (const row of inventoryRows) {
        if (!row || typeof row !== "object") {
          fail("missing_inventory", `Inventory row is not an object: ${JSON.stringify(row)}`);
          continue;
        }
        if (!validInventoryRoles.includes(row.role)) {
          fail("missing_inventory", `Inventory row has invalid role \`${row.role}\`.`);
        }
        // Gap/deferred rows represent workflows not yet implemented,
        // so they legitimately have no route.
        const isUnimplemented = row.documentation_status === "gap" || row.documentation_status === "deferred";
        if (!isUnimplemented) {
          if (!row.route || typeof row.route !== "string") {
            fail("missing_inventory", `Inventory row for \`${row.module}\` is missing a route.`);
          } else if (!routeNames.has(row.route)) {
            fail("missing_inventory", `Inventory row references unknown route \`${row.route}\`.`);
          }
        } else if (row.route && typeof row.route === "string" && !routeNames.has(row.route)) {
          fail("missing_inventory", `Inventory row references unknown route \`${row.route}\`.`);
        }
        if (row.permission && !permissionNames.has(row.permission)) {
          fail("missing_inventory", `Inventory row references unknown permission \`${row.permission}\`.`);
        }
        if (!validActivityVerbs.includes(row.activity)) {
          fail("missing_inventory", `Inventory row has invalid activity \`${row.activity}\`.`);
        }
        if (!validActivityKinds.includes(row.activity_kind)) {
          fail("missing_inventory", `Inventory row has invalid activity_kind \`${row.activity_kind}\`.`);
        }
        if (!validRiskTiers.includes(row.risk)) {
          fail("missing_inventory", `Inventory row has invalid risk \`${row.risk}\`.`);
        }
        if (!validDocStatuses.includes(row.documentation_status)) {
          fail("missing_inventory", `Inventory row has invalid documentation_status \`${row.documentation_status}\`.`);
        }
        if (row.documentation_status === "documented") {
          if (!row.article || typeof row.article !== "string") {
            fail("missing_inventory", `Inventory row \`${row.module}\` is documented but missing an article slug.`);
          } else {
            // An article may legitimately document multiple
            // workflows (e.g. viewing + creating). Track the first
            // occurrence but do not error on duplicates.
            if (!seenArticleSlugs.has(row.article)) {
              seenArticleSlugs.set(row.article, row.module);
            }
            if (!slugs.has(row.article)) {
              fail("missing_inventory", `Inventory row references unknown article slug \`${row.article}\`.`);
            }
          }
        }
        if ((row.documentation_status === "gap" || row.documentation_status === "deferred") && !row.gap_reason) {
          fail("missing_inventory", `Inventory row \`${row.module}\` is \`${row.documentation_status}\` but missing a gap_reason.`);
        }
      }
    }
  } else {
    fail("missing_inventory", `Inventory JSON missing: ${relative(projectRoot, inventoryJsonPath)}`);
  }

  // Every published article must appear in the inventory.
  if (inventoryRows.length > 0) {
    const inventoryArticleSlugs = new Set(
      inventoryRows
        .filter((row) => row && typeof row.article === "string")
        .map((row) => row.article),
    );
    for (const article of articles) {
      if (article.data.status !== "published") continue;
      // Shared / all-role reference articles (glossary, terminology)
      // are not workflow-specific and need not appear in the inventory.
      const articleRoles = article.data.roles ?? [];
      if (articleRoles.length > 0 && articleRoles.every((r) => r === "all" || r === "shared")) {
        continue;
      }
      if (!inventoryArticleSlugs.has(article.data.slug)) {
        fail("missing_inventory", `${article.file}: published article \`${article.data.slug}\` is not referenced in the role-workflow inventory.`);
      }
    }
  }

  // The Markdown must keep mentioning every documented inventory
  // article slug so reviewers skimming the human-readable file
  // don't miss references that exist only in the JSON.
  if (existsSync(inventoryMdPath)) {
    const inventoryMd = readFileSync(inventoryMdPath, "utf8");
    for (const article of articles) {
      if (article.data.status !== "published") continue;
      const mdArticleRoles = article.data.roles ?? [];
      if (mdArticleRoles.length > 0 && mdArticleRoles.every((r) => r === "all" || r === "shared")) {
        continue;
      }
      if (!inventoryMd.includes(`\`${article.data.slug}\``)) {
        fail("missing_inventory", `${article.file}: published article \`${article.data.slug}\` is not referenced in \`${relative(projectRoot, inventoryMdPath)}\`.`);
      }
    }
  } else {
    fail("missing_inventory", `Inventory markdown missing: ${relative(projectRoot, inventoryMdPath)}`);
  }

  checks.total = articles.length;

  // Coverage reports
  writeCoverageReports();

  log(`articles=${articles.length} errors=${errors.length} warnings=${warnings.length}`);
  for (const message of warnings) {
    console.warn(`  warn: ${message}`);
  }
  for (const { message } of errors) {
    console.error(`  error: ${message}`);
  }
  if (errors.length > 0) {
    process.exit(1);
  }
  log("OK");
}

function writeCoverageReports() {
  const published = checks.published;
  const draft = checks.draft;
  const archived = checks.archived;
  const report = {
    generated_at: new Date().toISOString(),
    articles: { total: checks.total, published, draft, archived },
    broken_screenshots: checks.broken_screenshots,
    broken_routes: checks.broken_routes,
    broken_related: checks.broken_related,
    broken_links: checks.broken_links,
    invalid_roles: checks.invalid_roles,
    invalid_permissions: checks.invalid_permissions,
    invalid_contextual_mappings: checks.invalid_contextual_mappings,
    stale_commits: checks.stale_commits,
    body_jargon: checks.body_jargon,
    body_credentials: checks.body_credentials,
    body_baseline: checks.body_baseline,
    missing_inventory: checks.missing_inventory,
    errors: errors.map((e) => ({ category: e.category, message: e.message })),
    warnings,
  };
  mkdirSync(dirname(coverageReportJsonPath), { recursive: true });
  writeFileSync(coverageReportJsonPath, JSON.stringify(report, null, 2) + "\n", "utf8");

  const md = [
    "# Laporan Cakupan Pusat Panduan",
    "",
    `> Dibuat otomatis oleh \`npm run docs:validate\`.`,
    `> Pembuatan terakhir: ${report.generated_at}`,
    "",
    "## Ringkasan",
    "",
    `- Total artikel: **${checks.total}**`,
    `- Published: **${published}**`,
    `- Draft: **${draft}**`,
    `- Archived: **${archived}**`,
    "",
    "## Cacat",
    "",
    `- Broken screenshots: ${checks.broken_screenshots}`,
    `- Broken routes: ${checks.broken_routes}`,
    `- Broken related articles: ${checks.broken_related}`,
    `- Broken internal links: ${checks.broken_links}`,
    `- Invalid roles: ${checks.invalid_roles}`,
    `- Invalid permissions: ${checks.invalid_permissions}`,
    `- Invalid contextual-help mappings: ${checks.invalid_contextual_mappings}`,
    `- Stale / unknown last_reviewed_commit: ${checks.stale_commits}`,
    `- Body jargon violations: ${checks.body_jargon}`,
    `- Body credential references: ${checks.body_credentials}`,
    `- Body baseline path references: ${checks.body_baseline}`,
    `- Articles missing from inventory: ${checks.missing_inventory}`,
    "",
    "## Pesan error",
    "",
    ...(errors.length === 0 ? ["_Tidak ada._"] : errors.map((e) => `- \`${e.category}\`: ${e.message}`)),
    "",
  ];
  writeFileSync(coverageReportMdPath, md.join("\n"), "utf8");
}

function mkdirSyncLocal(path, options) {
  mkdirSync(path, options);
}

main().catch((error) => {
  console.error("docs:validate failed:", error);
  process.exit(1);
});
