# Changelog

All notable changes to the Kojaya application are documented here.

## [Unreleased]

- Release-preparation implementation is complete.
- Manual PHP and shell verification has passed (full default suite, focused
  release-preflight checks, `bash -n bin/deploy.sh`, and `git diff --check`).
- PR GitHub Actions rerun, merge, green main CI, tag creation, and GitHub
  Release remain pending.
- Stable PHP 8.4-compatible Composer remediation is recorded in `composer.lock`;
  critical, high, and unknown advisories are cleared, with medium/low residual
  risk documented in the release-readiness report.

## [0.1.0] - 2026-07-17

### Added

- Document 01 P0 security and correctness remediation baseline.
- Document 02 payment and reservation state-machine remediation baseline.
- Document 03 PII encryption and blind-index foundation with staged rollout controls.
- Document 04 organization authorization and token metadata remediation baseline.
- Document 05 audit, pagination, and contract-test remediation baseline.
- PostgreSQL concurrency enforcement in the main CI baseline.

### Notes

- This is an internal alpha cooperative-backend baseline, not a production release.
- Legacy ERP quarantine, live payment/notification integrations, PII retirement,
  and final token cutover remain outside the completed release claim.
