#!/usr/bin/env node
/**
 * Regenerate docs/user-guide/role-responsibility-matrix.md from
 * resources/docs/user-guide/role-permissions.json.
 *
 * The JSON is the single source of truth (see Fase 10 of the
 * correction pass). The Markdown table is a presentation layer
 * that has to stay in sync. This script is idempotent: running
 * it twice produces the same output.
 */

import { readFileSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, "..");
const jsonPath = resolve(projectRoot, "resources/docs/user-guide/role-permissions.json");
const markdownPath = resolve(projectRoot, "docs/user-guide/role-responsibility-matrix.md");

const json = JSON.parse(readFileSync(jsonPath, "utf8"));
const roles = Object.keys(json.roles).sort();

const rows = [];
const allPerms = new Set();
for (const role of roles) {
  for (const perm of json.roles[role]) {
    allPerms.add(perm);
  }
}
const sortedPerms = Array.from(allPerms).sort();

for (const perm of sortedPerms) {
  const cells = roles.map((role) => (json.roles[role].includes(perm) ? "✅" : "—"));
  rows.push(`| \`${perm}\` | ${cells.join(" | ")} |`);
}

const header = `| Izin | ${roles.map((r) => r).join(" | ")} |`;
const divider = `| --- | ${roles.map(() => ":-:").join(" | ")} |`;

const totals = roles.map((role) => json.roles[role].length);
const summary = `| **Jumlah izin** | ${totals.map((t) => String(t)).join(" | ")} |`;

const intro = [
  "# Matriks Tanggung Jawab Peran",
  "",
  "> **Status:** Otomatis dihasilkan oleh",
  "> `node scripts/generate-role-matrix.mjs`.",
  "> Sumber data: `resources/docs/user-guide/role-permissions.json`",
  "> (Fase 10 dari correction pass).",
  "",
  "Matriks ini mencantumkan izin (permission) yang diberikan oleh",
  "`RolePermissionSeeder` kepada setiap peran koperasi. Sumber",
  "data mesin-mesin (JSON) dibandingkan dengan implementasi Spatie",
  "oleh `tests/Feature/Documentation/RolePermissionMatrixTest.php`.",
  "Jika izin ditambah, dihapus, atau dipindahkan antar peran, JSON",
  "harus diperbarui dan skrip ini dijalankan ulang.",
  "",
  "Tabel: ✅ = izin diberikan, — = tidak diberikan.",
  "",
  header,
  divider,
  ...rows,
  summary,
  "",
];

writeFileSync(markdownPath, intro.join("\n"));
console.log(`Wrote ${markdownPath}`);
console.log(`Roles: ${roles.join(", ")}`);
console.log(`Total unique permissions: ${sortedPerms.length}`);
