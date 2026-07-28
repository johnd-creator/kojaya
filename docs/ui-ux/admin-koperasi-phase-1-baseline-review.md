# Admin Koperasi Phase 1 — Baseline Review

## Scope

The Phase 1 registry covers the Admin Koperasi dashboard, member list and detail states, payment states, and dues states. Each state uses the existing cooperative visual-audit fixture and the `admin-koperasi` auth state. Member detail tablet captures remain intentionally skipped because the current cooperative viewport policy requires detail coverage on desktop and mobile only.

Registered states:

- Dashboard: `dashboard--dashboard--admin-koperasi`
- Members: `members--index--admin-default`, `members--index--pending-filter`, `members--index--no-results`, and pending-review, revision, and active detail states
- Payments: pending, empty, and selected
- Dues: open, partial, and no-results

## Review evidence

The local candidate capture ran against the isolated Playwright SQLite database with fixed audit data. It produced 42 passing visual cases and 3 policy skips across desktop, tablet, and mobile. A clean repeat compare produced the same result, and the Admin candidate hash check found 36 expected viewport/state matches with no mismatches.

The local host is Fedora Linux 44, so these local PNGs are development candidates only. They are not treated as canonical baselines: canonical adoption requires the repository UI Audit workflow on Ubuntu 24.04, followed by two clean captures with identical hashes.

## Safety checks

- Visual comparison tolerance: unchanged.
- Global screenshot masks: none added.
- Text or state masks: none added.
- Loading, error, 403, and invalid-fixture screens: not accepted as baselines.
- Runtime and accessibility review: performed through the Admin Koperasi Playwright scenarios; the final focused accessibility run passed 10 cases with no new waiver.
- Canonical Ubuntu capture: pending exact-final-SHA GitHub evidence.

Canonical adoption remains a release gate. Until the Ubuntu workflow completes and the candidate is reviewed twice, this branch must not be represented as having a final green visual baseline.
