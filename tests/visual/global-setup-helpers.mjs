import fs from "node:fs/promises";
import path from "node:path";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const execFileAsync = promisify(execFile);
const routeCoverageKeys = [
  "discovered_get_routes",
  "renderable_routes",
  "audited_routes",
  "excluded_routes",
  "uncovered_routes",
  "stale_registry_routes",
  "stale_exclusion_routes",
  "duplicate_screen_ids",
];

function routeCoveragePath(outputDir) {
  return path.join(outputDir, "coverage", "cooperative-route-coverage.json");
}

async function runCoverageCommand(command, args, options) {
  try {
    const result = await execFileAsync(command, args, options);

    if (result.stdout) {
      process.stdout.write(result.stdout);
    }

    if (result.stderr) {
      process.stderr.write(result.stderr);
    }

    return result;
  } catch (error) {
    if (error.stdout) {
      process.stdout.write(error.stdout);
    }

    if (error.stderr) {
      process.stderr.write(error.stderr);
    }

    throw error;
  }
}

export async function assertCanonicalRouteCoverageExists(outputDir) {
  const filePath = routeCoveragePath(outputDir);
  const content = await fs.readFile(filePath, "utf8");
  const coverage = JSON.parse(content);
  const missingKeys = routeCoverageKeys.filter((key) => typeof coverage[key] !== "number");

  if (missingKeys.length > 0) {
    throw new Error(`Canonical route coverage is missing required keys: ${missingKeys.join(", ")}`);
  }

  return coverage;
}

export async function prepareAuditOutput({
  outputDir = path.resolve("ui-audit-output"),
  authDir = path.resolve("tests/visual/.auth"),
  cwd = path.resolve("."),
  commandRunner = runCoverageCommand,
} = {}) {
  await fs.rm(outputDir, { recursive: true, force: true });
  await fs.mkdir(path.join(outputDir, "screenshots"), { recursive: true });
  await fs.mkdir(path.join(outputDir, "accessibility"), { recursive: true });
  await fs.mkdir(path.join(outputDir, "runtime"), { recursive: true });
  await fs.mkdir(authDir, { recursive: true });

  await commandRunner(
    "php",
    ["artisan", "--env=playwright", "ui-audit:coverage", "--no-interaction"],
    { cwd },
  );

  await assertCanonicalRouteCoverageExists(outputDir);
}
