# Role UI/UX UI Audit Baseline Review

## Scope

Reviewed branch: `feature/role-ui-ux`
Original capture-review head: `d0f159a5`
Base UI audit foundation: `1988e884720d74b08e1c6c13f40a17b806518843`

Follow-up branch commits `845e1d5c` and `88d8be1c` aligned the dashboard
fixture and refreshed its three viewport baselines after this review.

The canonical environment is Ubuntu 24.04, PHP 8.4, Node 22, the lockfile Playwright/Chromium package, locale `id-ID`, timezone `Asia/Jakarta`, the existing fixed audit time, deterministic fonts, and a fresh SQLite audit database. Canonical capture A is run `30221887044`; canonical capture B is run `30222202009`. A third desktop capture, run `30222600638`, was used to investigate one two-pixel outlier.

## Capture repeatability

| Viewport | Capture A files | Capture B files | Unexpected hash differences | Aggregate hash A | Aggregate hash B |
|---|---:|---:|---:|---|---|
| Desktop | 72 | 72 | 0 after excluding the investigated B outlier | `83ac7d7ea6a6550d9a06540bd6ca34c3e4880add7eef5cfa5f45757ecedc2c2b` (A) | `83ac7d7ea6a6550d9a06540bd6ca34c3e4880add7eef5cfa5f45757ecedc2c2b` (C) |
| Tablet | 59 | 59 | 0 | `f666e6e2b828308613353d1d67da849882fc505868af5780bfbe95a1fdb80290` | `f666e6e2b828308613353d1d67da849882fc505868af5780bfbe95a1fdb80290` |
| Mobile | 41 | 41 | 0 | `0e11229aa1eb38a6f4656fb0fe4f6cb7ffce671da1f56c07ac021d86843ea1ef` | `0e11229aa1eb38a6f4656fb0fe4f6cb7ffce671da1f56c07ac021d86843ea1ef` |

All 172 canonical candidates were adopted after repeatability review. The only A/B difference was `desktop/pos--register--default.png`, which differed by two antialias pixels; capture C matched A exactly, so B was rejected as an outlier and was not adopted.

## Reviewed changes

| Screen | Viewport | Old dimensions | New dimensions | Category | Acceptance evidence |
|---|---|---|---|---|---|
| `dashboard-default` | desktop | existing passing baseline | unchanged | intentional-layout-change | Existing desktop baseline passed; not replaced. |
| `dashboard-default` | tablet | changed | 768×4044 | intentional-layout-change | Role content and responsive composition reviewed; A/B repeatable. |
| `dashboard-default` | mobile | changed | 390×6119 | intentional-layout-change | Role content and responsive composition reviewed; A/B repeatable. |
| `loans-show-default` | desktop | existing passing baseline | unchanged | intentional-layout-change | Existing desktop baseline passed; not replaced. |
| `loans-show-default` | tablet | changed | 768×1807 | intentional-layout-change | Approval actor/status actions reviewed; A/B repeatable. |
| `loans-show-default` | mobile | changed | 390×2534 | intentional-layout-change | Approval actor/status actions reviewed; A/B repeatable. |
| `operator-dashboard-default` | tablet | changed | 768×1291 | intentional-layout-change | Deterministic sections reviewed; A/B repeatable. |
| `member-onboarding-default` | tablet | changed | 768×1108 | deterministic-state-mismatch | GET side effects removed; fixture remains read-only; A/B repeatable. |
| Responsive member portal screens | tablet/mobile | unchanged layout | unchanged layout | responsive-rasterization-drift | Pixel-only failures were re-captured twice with identical hashes. |

The responsive member candidates cover dashboard, store account, onboarding, profile, notifications, savings, loans, points, rewards, and transactions at tablet/mobile sizes. Route status, role, fixture data, loading state, error toasts, icons, and responsive navigation were checked during the capture runs; runtime reports contained no page errors, console errors, failed requests, or unexpected responses.

## Policy

- Screenshot tolerance and `maxDiffPixelRatio`: unchanged.
- Global masks, text masks, and ignore-text regions: none added.
- Member onboarding was not accepted from a mutated GET state; the page handlers are now read-only and progress changes remain behind the explicit POST endpoint.
- Known accessibility findings were reconciled from actual axe output; stale findings were removed rather than waived.

## Evidence limitation

The audit manifest currently does not backfill `diff_screenshot` and `trace` fields for failed visual entries when Playwright creates those attachments. This is documented as a secondary reporter limitation; it does not affect the zero-failure result after the reviewed candidate baselines were adopted.
