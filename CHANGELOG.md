# Changelog

All notable changes to the Kojaya application are documented here.

## [Unreleased]

- `v0.1.0` was tagged and published as an internal-alpha GitHub pre-release at
  `ad8bc3afc9b62f549e4f054e181ef9decbecb341` (PR #10 merged; main CI green).
- Document 08 (release staging, staging operations, and mobile pilot readiness)
  is merged to `main` as post-release roadmap; it is not part of the `v0.1.0`
  tag (post-release commit `ed8f2026c35508471385ad49ae23a24189b642b1`).
- Workstreams 08-B through 08-G (staging deployment proof, backup/restore/
  rollback, observability, secret validation, Android pilot, and legacy test
  recovery) remain planned, not executed.
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

### Release identity

- Internal Alpha / Cooperative Backend Baseline / Not Production Release.
- Tag `v0.1.0` (immutable) points to `ad8bc3afc9b62f549e4f054e181ef9decbecb341`.
- GitHub Release `v0.1.0` is published as a pre-release.

### Notes

- This is an internal alpha cooperative-backend baseline, not a production release.
- Legacy ERP quarantine, live payment/notification integrations, PII retirement,
  and final token cutover remain outside the completed release claim.
