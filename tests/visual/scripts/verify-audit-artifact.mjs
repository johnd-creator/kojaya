import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const supportedModes = new Set(["capture", "compare", "accessibility", "full"]);
const supportedViewports = new Set(["desktop", "tablet", "mobile", "all"]);
const supportedScopes = new Set([
  "cooperative",
  "member",
  "store-credit",
  "pos",
  "all",
]);
const shaPattern = /^[0-9a-f]{40}$/;
const routeKeys = [
  "discovered_get_routes",
  "renderable_routes",
  "audited_routes",
  "excluded_routes",
  "uncovered_routes",
  "stale_registry_routes",
  "stale_exclusion_routes",
  "duplicate_screen_ids",
];
const visualKeys = [
  "desktop_expected_screens",
  "desktop_executed_screens",
  "desktop_passed_screens",
  "desktop_failed_screens",
  "desktop_skipped_screens",
  "tablet_expected_screens",
  "tablet_executed_screens",
  "tablet_passed_screens",
  "tablet_failed_screens",
  "tablet_skipped_screens",
  "mobile_expected_screens",
  "mobile_executed_screens",
  "mobile_passed_screens",
  "mobile_failed_screens",
  "mobile_skipped_screens",
  "expected_entries",
  "generated_entries",
  "passed_entries",
  "failed_entries",
  "skipped_entries",
];

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch (error) {
    throw new Error(
      `Unable to read JSON artifact ${filePath}: ${error.message}`,
    );
  }
}

function requireString(object, key, errors) {
  if (typeof object[key] !== "string" || object[key].trim() === "") {
    errors.push(`Missing required string field: ${key}`);
  }
}

function requireSha(object, key, errors) {
  if (typeof object[key] !== "string" || !shaPattern.test(object[key])) {
    errors.push(`Invalid SHA field: ${key}`);
  }
}

function requireCoverageKeys(object, keys, label, errors) {
  for (const key of keys) {
    if (!Number.isInteger(object[key]) || object[key] < 0) {
      errors.push(`Invalid ${label} field: ${key}`);
    }
  }
}

function stableJson(value) {
  if (Array.isArray(value)) return value.map(stableJson);
  if (value && typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value)
        .sort(([left], [right]) => left.localeCompare(right))
        .map(([key, entry]) => [key, stableJson(entry)]),
    );
  }
  return value;
}

export function verifyAuditArtifact({
  outputDir = path.resolve("ui-audit-output"),
  environment = process.env,
} = {}) {
  const errors = [];
  const manifestPath = path.join(outputDir, "manifest.json");
  const routeCoveragePath = path.join(
    outputDir,
    "coverage",
    "cooperative-route-coverage.json",
  );
  const visualCoveragePath = path.join(
    outputDir,
    "coverage",
    "visual-entry-coverage.json",
  );
  let manifest;
  let routeCoverage;
  let visualCoverage;

  try {
    manifest = readJson(manifestPath);
  } catch (error) {
    errors.push(error.message);
  }
  try {
    routeCoverage = readJson(routeCoveragePath);
  } catch (error) {
    errors.push(error.message);
  }
  try {
    visualCoverage = readJson(visualCoveragePath);
  } catch (error) {
    errors.push(error.message);
  }

  if (!manifest || typeof manifest !== "object") {
    errors.push("Manifest is not an object.");
  } else {
    if (manifest.framework_version !== 3)
      errors.push("Unsupported manifest framework_version; expected 3.");
    for (const key of [
      "event_name",
      "mode",
      "viewport",
      "scope",
      "default_branch",
    ])
      requireString(manifest, key, errors);
    for (const key of [
      "head_sha",
      "tested_sha",
      "base_sha",
      "default_branch_sha",
    ])
      requireSha(manifest, key, errors);
    if (!supportedModes.has(manifest.mode))
      errors.push(`Unsupported manifest mode: ${manifest.mode}`);
    if (!supportedViewports.has(manifest.viewport))
      errors.push(`Unsupported manifest viewport: ${manifest.viewport}`);
    if (!supportedScopes.has(manifest.scope))
      errors.push(`Unsupported manifest scope: ${manifest.scope}`);

    if (manifest.event_name === "pull_request") {
      if (
        !Number.isInteger(manifest.pull_request_number) ||
        manifest.pull_request_number < 1
      ) {
        errors.push(
          "Pull-request artifacts require a positive pull_request_number.",
        );
      }
    } else if (
      manifest.event_name === "workflow_dispatch" &&
      manifest.pull_request_number !== null
    ) {
      errors.push(
        "Workflow-dispatch artifacts require pull_request_number=null.",
      );
    }

    const expectedIdentity = {
      event_name: environment.UI_AUDIT_EVENT_NAME,
      head_sha: environment.UI_AUDIT_HEAD_SHA,
      tested_sha: environment.UI_AUDIT_TESTED_SHA,
      base_sha: environment.UI_AUDIT_BASE_SHA,
      mode: environment.UI_AUDIT_REQUESTED_MODE,
      viewport: environment.UI_AUDIT_REQUESTED_VIEWPORT,
      scope: environment.UI_AUDIT_REQUESTED_SCOPE,
      default_branch: environment.UI_AUDIT_DEFAULT_BRANCH,
      default_branch_sha: environment.UI_AUDIT_DEFAULT_BRANCH_SHA,
    };
    for (const [key, expected] of Object.entries(expectedIdentity)) {
      if (
        expected !== undefined &&
        expected !== "" &&
        manifest[key] !== expected
      ) {
        errors.push(`Manifest ${key} does not match workflow metadata.`);
      }
    }
    if (environment.UI_AUDIT_PULL_REQUEST_NUMBER !== undefined) {
      const expectedNumber =
        environment.UI_AUDIT_PULL_REQUEST_NUMBER === ""
          ? null
          : Number(environment.UI_AUDIT_PULL_REQUEST_NUMBER);
      if (manifest.pull_request_number !== expectedNumber)
        errors.push(
          "Manifest pull_request_number does not match workflow metadata.",
        );
    }
    if (manifest.event_name === "workflow_dispatch") {
      if (!shaPattern.test(environment.UI_AUDIT_DEFAULT_BRANCH_SHA ?? ""))
        errors.push("Missing resolved default-branch SHA.");
      if (manifest.base_sha !== environment.UI_AUDIT_DEFAULT_BRANCH_SHA)
        errors.push(
          "Dispatch base_sha does not match resolved default-branch SHA.",
        );
    }
  }

  if (!routeCoverage || typeof routeCoverage !== "object") {
    errors.push("Canonical route coverage is missing or invalid.");
  } else {
    requireCoverageKeys(routeCoverage, routeKeys, "route coverage", errors);
    for (const key of [
      "uncovered_routes",
      "stale_registry_routes",
      "stale_exclusion_routes",
      "duplicate_screen_ids",
    ]) {
      if (routeCoverage[key] !== 0)
        errors.push(
          `Canonical route coverage is not clean: ${key}=${routeCoverage[key]}`,
        );
    }
    if (
      manifest &&
      JSON.stringify(stableJson(manifest.route_coverage)) !==
        JSON.stringify(stableJson(routeCoverage))
    ) {
      errors.push(
        "Manifest route_coverage does not match the canonical route artifact.",
      );
    }
  }

  if (!visualCoverage || typeof visualCoverage !== "object") {
    errors.push("Visual-entry coverage is missing or invalid.");
  } else {
    requireCoverageKeys(
      visualCoverage,
      visualKeys,
      "visual-entry coverage",
      errors,
    );
    if (
      manifest &&
      JSON.stringify(stableJson(manifest.coverage)) !==
        JSON.stringify(stableJson(visualCoverage))
    ) {
      errors.push("Manifest coverage does not match visual-entry coverage.");
    }
  }

  if (errors.length > 0) {
    throw new Error(
      `UI audit artifact contract failed:\n- ${errors.join("\n- ")}`,
    );
  }

  return { manifest, routeCoverage, visualCoverage };
}

const isCli =
  process.argv[1] &&
  path.resolve(process.argv[1]) ===
    path.resolve(fileURLToPath(import.meta.url));
if (isCli) {
  try {
    verifyAuditArtifact();
    console.log(JSON.stringify({ artifact: "valid" }));
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
