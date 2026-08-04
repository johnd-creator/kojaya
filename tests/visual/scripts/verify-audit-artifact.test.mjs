import assert from "node:assert/strict";
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { test } from "node:test";
import { verifyAuditArtifact } from "./verify-audit-artifact.mjs";

const head = "1".repeat(40);
const tested = "2".repeat(40);
const base = "3".repeat(40);

function routeCoverage(overrides = {}) {
  return {
    discovered_get_routes: 75,
    renderable_routes: 61,
    audited_routes: 61,
    excluded_routes: 14,
    uncovered_routes: 0,
    stale_registry_routes: 0,
    stale_exclusion_routes: 0,
    duplicate_screen_ids: 0,
    ...overrides,
  };
}

function visualCoverage(total = 72) {
  return {
    desktop_expected_screens: total,
    desktop_executed_screens: total,
    desktop_passed_screens: total,
    desktop_failed_screens: 0,
    desktop_skipped_screens: 0,
    tablet_expected_screens: 0,
    tablet_executed_screens: 0,
    tablet_passed_screens: 0,
    tablet_failed_screens: 0,
    tablet_skipped_screens: 0,
    mobile_expected_screens: 0,
    mobile_executed_screens: 0,
    mobile_passed_screens: 0,
    mobile_failed_screens: 0,
    mobile_skipped_screens: 0,
    expected_entries: total,
    generated_entries: total,
    passed_entries: total,
    failed_entries: 0,
    skipped_entries: 0,
  };
}

function fixture({
  event = "pull_request",
  mode = "compare",
  viewport = "desktop",
  scope = "all",
  route = routeCoverage(),
  visual = visualCoverage(),
  manifestOverrides = {},
} = {}) {
  const root = mkdtempSync(join(tmpdir(), "kojaya-audit-artifact-"));
  const outputDir = join(root, "ui-audit-output");
  mkdirSync(join(outputDir, "coverage"), { recursive: true });
  const manifest = {
    application: "Kojaya",
    framework_version: 3,
    head_sha: head,
    tested_sha: tested,
    base_sha: base,
    default_branch: "main",
    default_branch_sha: base,
    event_name: event,
    pull_request_number: event === "pull_request" ? 23 : null,
    mode,
    viewport,
    scope,
    coverage: visual,
    route_coverage: route,
    ...manifestOverrides,
  };
  writeFileSync(join(outputDir, "manifest.json"), JSON.stringify(manifest));
  writeFileSync(
    join(outputDir, "coverage", "cooperative-route-coverage.json"),
    JSON.stringify(route),
  );
  writeFileSync(
    join(outputDir, "coverage", "visual-entry-coverage.json"),
    JSON.stringify(visual),
  );

  return {
    outputDir,
    environment: {
      UI_AUDIT_EVENT_NAME: event,
      UI_AUDIT_HEAD_SHA: head,
      UI_AUDIT_TESTED_SHA: tested,
      UI_AUDIT_BASE_SHA: base,
      UI_AUDIT_PULL_REQUEST_NUMBER: event === "pull_request" ? "23" : "",
      UI_AUDIT_REQUESTED_MODE: mode,
      UI_AUDIT_REQUESTED_VIEWPORT: viewport,
      UI_AUDIT_REQUESTED_SCOPE: scope,
      UI_AUDIT_DEFAULT_BRANCH_SHA: base,
      UI_AUDIT_DEFAULT_BRANCH: "main",
    },
    cleanup: () => rmSync(root, { recursive: true, force: true }),
  };
}

test("accepts a valid pull-request artifact fixture", () => {
  const fixtureData = fixture();
  try {
    assert.doesNotThrow(() => verifyAuditArtifact(fixtureData));
  } finally {
    fixtureData.cleanup();
  }
});

test("accepts a valid workflow-dispatch artifact fixture", () => {
  const fixtureData = fixture({
    event: "workflow_dispatch",
    mode: "full",
    viewport: "all",
    visual: visualCoverage(172),
  });
  try {
    assert.doesNotThrow(() => verifyAuditArtifact(fixtureData));
  } finally {
    fixtureData.cleanup();
  }
});

test("rejects missing requested metadata", () => {
  const fixtureData = fixture({
    manifestOverrides: {
      mode: undefined,
      viewport: undefined,
      scope: undefined,
    },
  });
  try {
    assert.throws(
      () => verifyAuditArtifact(fixtureData),
      /mode|viewport|scope/,
    );
  } finally {
    fixtureData.cleanup();
  }
});

test("rejects malformed SHA and mismatched dispatch base", () => {
  const malformed = fixture({ manifestOverrides: { head_sha: "not-a-sha" } });
  try {
    assert.throws(() => verifyAuditArtifact(malformed), /Invalid SHA field/);
  } finally {
    malformed.cleanup();
  }

  const dispatch = fixture({
    event: "workflow_dispatch",
    mode: "full",
    viewport: "all",
    visual: visualCoverage(172),
    manifestOverrides: { base_sha: "4".repeat(40) },
  });
  try {
    assert.throws(() => verifyAuditArtifact(dispatch), /Dispatch base_sha/);
  } finally {
    dispatch.cleanup();
  }
});

test("rejects visual counts masquerading as route coverage and dirty route values", () => {
  const masquerading = fixture({ route: visualCoverage() });
  try {
    assert.throws(
      () => verifyAuditArtifact(masquerading),
      /route coverage field|Manifest route_coverage/,
    );
  } finally {
    masquerading.cleanup();
  }

  const dirty = fixture({ route: routeCoverage({ uncovered_routes: 1 }) });
  try {
    assert.throws(() => verifyAuditArtifact(dirty), /uncovered_routes/);
  } finally {
    dirty.cleanup();
  }
});
