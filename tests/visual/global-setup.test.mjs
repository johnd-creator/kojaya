import assert from "node:assert/strict";
import fs from "node:fs/promises";
import os from "node:os";
import path from "node:path";
import test from "node:test";
import { assertCanonicalRouteCoverageExists, prepareAuditOutput } from "./global-setup-helpers.mjs";

const routeCoverage = {
  discovered_get_routes: 75,
  renderable_routes: 61,
  audited_routes: 61,
  excluded_routes: 14,
  uncovered_routes: 0,
  stale_registry_routes: 0,
  stale_exclusion_routes: 0,
  duplicate_screen_ids: 0,
};

async function fixture() {
  const root = await fs.mkdtemp(path.join(os.tmpdir(), "kojaya-global-setup-"));
  return {
    root,
    outputDir: path.join(root, "ui-audit-output"),
    authDir: path.join(root, ".auth"),
  };
}

test("resets stale output and generates fresh canonical route coverage", async () => {
  const { root, outputDir, authDir } = await fixture();
  await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
  await fs.mkdir(path.join(outputDir, "screenshots"), { recursive: true });
  await fs.mkdir(path.join(outputDir, "accessibility"), { recursive: true });
  await fs.mkdir(path.join(outputDir, "runtime"), { recursive: true });
  await fs.writeFile(path.join(outputDir, "manifest.json"), "stale");
  await fs.writeFile(path.join(outputDir, "coverage", "visual-entry-coverage.json"), "stale");
  await fs.writeFile(path.join(outputDir, "screenshots", "stale.png"), "stale");
  await fs.writeFile(path.join(outputDir, "accessibility", "stale.json"), "stale");
  await fs.writeFile(path.join(outputDir, "runtime", "stale.json"), "stale");
  await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.json"), JSON.stringify({ stale: true }));

  let command;
  await prepareAuditOutput({
    outputDir,
    authDir,
    cwd: root,
    commandRunner: async (executable, args, options) => {
      command = { executable, args, options };
      await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
      await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.json"), JSON.stringify(routeCoverage));
      return { stdout: "", stderr: "" };
    },
  });

  assert.deepEqual(command, {
    executable: "php",
    args: ["artisan", "--env=playwright", "ui-audit:coverage", "--no-interaction"],
    options: { cwd: root },
  });
  await assert.rejects(() => fs.access(path.join(outputDir, "manifest.json")));
  await assert.rejects(() => fs.access(path.join(outputDir, "coverage", "visual-entry-coverage.json")));
  await assert.rejects(() => fs.access(path.join(outputDir, "screenshots", "stale.png")));
  await assert.rejects(() => fs.access(path.join(outputDir, "accessibility", "stale.json")));
  await assert.rejects(() => fs.access(path.join(outputDir, "runtime", "stale.json")));
  assert.deepEqual(await assertCanonicalRouteCoverageExists(outputDir), routeCoverage);
});

test("does not accept a stale canonical file when generation produces nothing", async () => {
  const { outputDir, authDir } = await fixture();
  await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
  await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.json"), JSON.stringify(routeCoverage));

  await assert.rejects(
    () => prepareAuditOutput({ outputDir, authDir, commandRunner: async () => ({ stdout: "", stderr: "" }) }),
    /ENOENT/,
  );
});

test("fails when the Artisan coverage command fails", async () => {
  const { outputDir, authDir } = await fixture();

  await assert.rejects(
    () => prepareAuditOutput({
      outputDir,
      authDir,
      commandRunner: async () => {
        throw new Error("coverage command failed");
      },
    }),
    /coverage command failed/,
  );
  await assert.rejects(() => fs.access(path.join(outputDir, "coverage", "cooperative-route-coverage.json")));
});

test("fails when the coverage command succeeds without creating canonical JSON", async () => {
  const { outputDir, authDir } = await fixture();

  await assert.rejects(
    () => prepareAuditOutput({ outputDir, authDir, commandRunner: async () => ({ stdout: "", stderr: "" }) }),
    /ENOENT/,
  );
});

test("rejects malformed canonical JSON after generation", async () => {
  const { outputDir, authDir } = await fixture();

  await assert.rejects(
    () => prepareAuditOutput({
      outputDir,
      authDir,
      commandRunner: async () => {
        await fs.mkdir(path.join(outputDir, "coverage"), { recursive: true });
        await fs.writeFile(path.join(outputDir, "coverage", "cooperative-route-coverage.json"), "not-json");
        return { stdout: "", stderr: "" };
      },
    }),
    /Unexpected token|JSON/,
  );
});
