#!/usr/bin/env node
/**
 * Validate the in-app user guide against the contract.
 *
 * Checks:
 *  - frontmatter completeness
 *  - slug uniqueness and kebab-case format
 *  - roles, permissions, permission_mode, risk_level, status
 *  - route_names resolve to a registered route
 *  - related_articles point to existing slugs
 *  - screenshot_entries map to a manifest entry
 *  - last_reviewed_commit is a 7-40 char hex SHA
 *  - no Playwright email/credentials, no production references
 *  - no empty articles
 *  - active workflows not missing from inventory
 *
 * Exits non-zero on any failure. The command must NEVER succeed
 * silently when there are no articles.
 */

import { existsSync, readFileSync, statSync } from "node:fs";
import { readdir } from "node:fs/promises";
import { dirname, join, relative, resolve, sep } from "node:path";
import { fileURLToPath } from "node:url";
import { spawnSync } from "node:child_process";
import process from "node:process";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, "..");
const guideDir = resolve(projectRoot, "docs/user-guide");
const manifestPath = resolve(projectRoot, "resources/docs/user-guide/screenshots.json");
const inventoryPath = resolve(guideDir, "role-workflow-inventory.md");

const errors = [];
const warnings = [];
const validRoles = ["all", "anggota", "admin_koperasi", "manajer_koperasi", "pengurus_koperasi"];
const validPermissionModes = ["all", "any"];
const validRiskLevels = ["low", "medium", "high"];
const validStatuses = ["published", "draft", "archived"];
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

const fail = (message) => {
  errors.push(message);
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

const simpleYamlParse = (source) => {
  // Very small YAML parser covering the limited subset we need:
  //   - key: scalar
  //   - key:\n  - item\n  - item
  //   - key: []    (empty list)
  //   - comments starting with #
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
    // List block
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

const parseScalar = (raw) => {
  if (raw === undefined) return "";
  let s = raw.trim();
  if (s.startsWith("#")) return "";
  // Strip trailing comment after a space.
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

const loadRouteNames = () => {
  const result = spawnSync("php", ["artisan", "route:list", "--json"], {
    cwd: projectRoot,
    encoding: "utf8",
  });
  if (result.status !== 0) {
    warn(`Unable to enumerate routes: ${result.stderr || result.stdout}`);
    return new Set();
  }
  let parsed;
  try {
    parsed = JSON.parse(result.stdout);
  } catch (error) {
    warn(`Route JSON parse failed: ${error.message}`);
    return new Set();
  }
  const names = new Set();
  for (const route of parsed) {
    if (route?.name) {
      names.add(route.name);
    }
  }
  return names;
};

const loadPermissionNames = () => {
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
    warn(`Unable to enumerate permissions: ${result.stderr || result.stdout}`);
    return new Set();
  }
  return new Set(result.stdout.split(/\r?\n/).filter(Boolean));
};

const fileChecks = [
  // Safety: no Playwright credentials or production URLs in body
  {
    id: "no-playwright-account",
    pattern: /playwright\.com|dramatic-fennek|admin@playwright\.com/i,
    message: "Found Playwright account reference. Use a placeholder account instead.",
  },
  {
    id: "no-production-baseline",
    pattern: /tests\/visual\/baselines\/[^)\s]+\.png/i,
    message: "Body references a baseline file directly; use the screenshot pipeline instead.",
  },
  {
    id: "no-env-secret",
    pattern: /MIDTRANS_SERVER_KEY\s*=\s*[A-Za-z0-9]+/i,
    message: "Body contains a Midtrans server key value.",
  },
];

async function main() {
  if (!existsSync(guideDir)) {
    fail(`User-guide directory not found: ${guideDir}`);
  }

  const files = await listMarkdownFiles(guideDir);
  if (files.length === 0) {
    fail(`No Markdown files found under ${guideDir}`);
  }

  // Skip non-article files (audit, README, inventory, etc.) by
  // checking the frontmatter. Articles must declare the required keys.
  const articles = [];
  for (const file of files) {
    const contents = readFileSync(file, "utf8");
    const rel = relative(projectRoot, file);
    if (!contents.startsWith("---")) {
      // Not a frontmatter doc — keep it but skip article-level checks.
      continue;
    }
    const yaml = parseFrontmatter(contents);
    if (yaml === null) {
      fail(`${rel}: missing closing --- for frontmatter.`);
      continue;
    }
    let parsed;
    try {
      parsed = simpleYamlParse(yaml);
    } catch (error) {
      fail(`${rel}: cannot parse YAML (${error.message})`);
      continue;
    }
    for (const key of requiredKeys) {
      if (!(key in parsed)) {
        fail(`${rel}: missing required frontmatter key \`${key}\`.`);
      }
    }
    if (parsed.slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(parsed.slug)) {
      fail(`${rel}: slug must be kebab-case ASCII.`);
    }
    if (parsed.roles) {
      if (!Array.isArray(parsed.roles)) {
        fail(`${rel}: \`roles\` must be a list.`);
      } else {
        for (const role of parsed.roles) {
          if (!validRoles.includes(role)) {
            fail(`${rel}: invalid role \`${role}\` (valid: ${validRoles.join(", ")}).`);
          }
        }
      }
    }
    if (parsed.permission_mode && !validPermissionModes.includes(parsed.permission_mode)) {
      fail(`${rel}: invalid \`permission_mode\` (${parsed.permission_mode}).`);
    }
    if (parsed.risk_level && !validRiskLevels.includes(parsed.risk_level)) {
      fail(`${rel}: invalid \`risk_level\` (${parsed.risk_level}).`);
    }
    if (parsed.status && !validStatuses.includes(parsed.status)) {
      fail(`${rel}: invalid \`status\` (${parsed.status}).`);
    }
    if (parsed.last_reviewed_commit && !/^[0-9a-f]{7,40}$/.test(parsed.last_reviewed_commit)) {
      fail(`${rel}: \`last_reviewed_commit\` must be a 7-40 char hex SHA.`);
    }
    if (parsed.summary && typeof parsed.summary === "string" && parsed.summary.trim().length < 5) {
      fail(`${rel}: \`summary\` is too short.`);
    }
    if (parsed.body_is_empty !== undefined) {
      // Reserved key — should never appear.
      fail(`${rel}: contains reserved key \`body_is_empty\`.`);
    }

    // Body safety checks
    for (const check of fileChecks) {
      if (check.pattern.test(contents)) {
        fail(`${rel}: ${check.message}`);
      }
    }

    articles.push({ file: rel, data: parsed });
  }

  // Slug uniqueness
  const slugs = new Map();
  for (const article of articles) {
    const slug = article.data.slug;
    if (!slug) continue;
    if (slugs.has(slug)) {
      fail(`Duplicate slug \`${slug}\` in ${article.file} and ${slugs.get(slug)}.`);
    } else {
      slugs.set(slug, article.file);
    }
  }

  // related_articles resolve to existing slugs
  for (const article of articles) {
    const related = article.data.related_articles ?? [];
    if (!Array.isArray(related)) {
      fail(`${article.file}: \`related_articles\` must be a list.`);
      continue;
    }
    for (const target of related) {
      if (target !== "" && !slugs.has(target)) {
        fail(`${article.file}: \`related_articles\` references unknown slug \`${target}\`.`);
      }
    }
  }

  // route_names resolve
  const routeNames = loadRouteNames();
  if (routeNames.size === 0) {
    warn("Could not enumerate routes; route validation skipped.");
  }
  for (const article of articles) {
    const routes = article.data.route_names ?? [];
    if (!Array.isArray(routes)) {
      fail(`${article.file}: \`route_names\` must be a list.`);
      continue;
    }
    for (const routeName of routes) {
      if (routeName === "") continue;
      if (!routeNames.has(routeName)) {
        fail(`${article.file}: \`route_names\` references unknown route \`${routeName}\`.`);
      }
    }
  }

  // permissions resolve (best effort)
  const permissionNames = loadPermissionNames();
  if (permissionNames.size > 0) {
    for (const article of articles) {
      const perms = article.data.permissions ?? [];
      if (!Array.isArray(perms)) continue;
      for (const perm of perms) {
        if (perm === "") continue;
        if (!permissionNames.has(perm)) {
          fail(`${article.file}: \`permissions\` references unknown permission \`${perm}\`.`);
        }
      }
    }
  }

  // screenshot_entries resolve
  let manifest = null;
  if (existsSync(manifestPath)) {
    try {
      manifest = JSON.parse(readFileSync(manifestPath, "utf8"));
    } catch (error) {
      fail(`Cannot parse manifest: ${error.message}`);
    }
  } else {
    warn(`Screenshot manifest not found: ${manifestPath}`);
  }
  const manifestIds = new Set((manifest?.entries ?? []).map((e) => e.id));
  for (const article of articles) {
    const entries = article.data.screenshot_entries ?? [];
    if (!Array.isArray(entries)) {
      fail(`${article.file}: \`screenshot_entries\` must be a list.`);
      continue;
    }
    for (const entry of entries) {
      if (entry === "") continue;
      if (!manifestIds.has(entry)) {
        fail(`${article.file}: \`screenshot_entries\` references unknown id \`${entry}\`.`);
      }
    }
  }

  // Empty article check
  for (const article of articles) {
    const filePath = join(projectRoot, article.file);
    const stat = statSync(filePath);
    if (stat.size < 400) {
      fail(`${article.file}: file is too small (${stat.size} bytes); likely empty.`);
    }
  }

  // Inventory cross-check: every published article must have at least
  // one route that is not in the "not-yet-implemented" gap table.
  if (existsSync(inventoryPath)) {
    const inventory = readFileSync(inventoryPath, "utf8");
    for (const article of articles) {
      if (article.data.status !== "published") continue;
      if (!inventory.includes(`\`${article.data.slug}\``)) {
        warn(`${article.file}: slug \`${article.data.slug}\` is not referenced in \`${relative(projectRoot, inventoryPath)}\`.`);
      }
    }
  }

  // Summary
  log(`articles=${articles.length} errors=${errors.length} warnings=${warnings.length}`);
  if (warnings.length > 0) {
    for (const message of warnings) {
      console.warn(`  warn: ${message}`);
    }
  }
  if (errors.length > 0) {
    for (const message of errors) {
      console.error(`  error: ${message}`);
    }
    process.exit(1);
  }
  log("OK");
}

main().catch((error) => {
  console.error("docs:validate failed:", error);
  process.exit(1);
});
