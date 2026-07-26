# Role UI/UX UI Audit Baseline Review

## Scope

Reviewed branch: `feature/role-ui-ux`  
Reviewed local head: `d0f159a5`  
Base UI audit foundation: `1988e884720d74b08e1c6c13f40a17b806518843`

The requested canonical environment is Ubuntu 24.04, PHP 8.4, Node 22, the lockfile Playwright/Chromium package, locale `id-ID`, timezone `Asia/Jakarta`, the existing fixed audit time, committed/local fonts, and a fresh SQLite audit database. GitHub capture was intentionally not started or awaited in this correction because the user requested the task stop without waiting for Actions. The repeatability evidence below is from two clean local captures.

## Capture repeatability

| Viewport | Capture A files | Capture B files | Unexpected hash differences | Aggregate hash A | Aggregate hash B |
|---|---:|---:|---:|---|---|
| Tablet | 59 | 59 | 0 | `141b21f3b5636746bb59c452ca4e648282b77388bbce40cff9cc630d500a2542` | `141b21f3b5636746bb59c452ca4e648282b77388bbce40cff9cc630d500a2542` |
| Mobile | 41 | 41 | 0 | `8ecbbe550e774be43ccd29b1e1181d75e3dd6958fd2f67be28b67e3b30db8518` | `8ecbbe550e774be43ccd29b1e1181d75e3dd6958fd2f67be28b67e3b30db8518` |

Desktop baselines were already passing and were not replaced during this correction. Only failed responsive candidates were adopted after the two-capture check.

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
