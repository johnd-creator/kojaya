# Taste (Continuously Learned by [CommandCode][cmd])

[cmd]: https://commandcode.ai/

# vue
- reka-ui SelectItem requires a non-empty string value prop; use sentinel constants (e.g., `__all_statuses__`) instead of empty strings for "all/clear filter" options. Confidence: 0.70

# wayfinder
- When regenerating Wayfinder routes, use `php artisan wayfinder:generate --with-form --no-interaction` to minimize churn in generated files; after generation, revert unrelated generated files to keep diff focused on actual changes. Confidence: 0.70

# inertia
- Always pass a group name as the second argument to `Inertia::defer()` (e.g., `Inertia::defer(fn () => $data, 'groupName')`) so that `loadDeferredProps('groupName')` works in PHPUnit tests. Confidence: 0.70
- When a page's filter state must stay synced with query params (e.g., after deferred prop reloads), pass filters as a typed `filters` prop from the controller, initialize local reactive state from `props.filters`, and strip empty values with a `cleanFilters()` helper before calling `router.get()`. This prevents the mismatch where Vue state resets to defaults while the URL still carries active filters. Confidence: 0.60

# php
- When extracting a `Cache::remember()` closure into a typed private method, use `CarbonInterface` instead of `Carbon` in the type hint to accept both `Carbon` and `CarbonImmutable` instances. Confidence: 0.70

# i18n
- User-facing UI strings (empty states, button labels, aria-labels) should use Bahasa Indonesia, matching the project's primary language. Confidence: 0.70

# agents
- Treat AGENTS.md as the canonical project knowledge base; read it before starting tasks and update it with new architecture decisions, role hierarchies, credentials/config references, and workflow conventions so other AI agents stay in sync. Confidence: 0.80

# data-table
- DataTable component should not have internal `max-h` vertical scroll limiting; let the page layout control scrolling to avoid double scrollbars. Confidence: 0.65
- For paginated tables, use 15 items per page (`paginate(15)`) instead of the default 10. Confidence: 0.70

# a11y
- Inline error messages in form components need `role="alert"` for screen reader announcement; file input errors should also reset `target.value = ""` so the same file can be re-selected after correction. Confidence: 0.65
- For modal/dialog a11y hardening, prefer the existing dialog primitive (`resources/js/components/ui/dialog`) over custom overlay divs — it provides consistent focus trap, Escape handling, and focus restore. If custom overlay is unavoidable, add explicit focus management: focus close button on open, trap Tab within modal, restore focus to trigger on close. Confidence: 0.60

# midtrans
- Midtrans Sandbox payment channels (QRIS, VA, E-Wallet) must be manually activated in the Midtrans Dashboard under Settings → Payment Channels; sandbox mode does not auto-activate all channels. Test channel availability before debugging integration code. Confidence: 0.65

# php-runtime
- This project's `composer.lock` pins `phpoffice/phpspreadsheet` to a version that requires `php >=7.4 <8.5`, so PHP 8.5 (system default) breaks `composer install` with a platform constraint error. Use the `php84` interpreter (installed at `/usr/bin/php84`, version 8.4.x) for Composer, `artisan`, and PHPUnit runs. The default `/usr/bin/php` (8.5.x) is only safe for runtime commands that don't validate composer.lock platform constraints. Confidence: 0.90

# docs-validator
- The in-app user-guide validator (`scripts/validate-user-guide.mjs`) is designed as a hard gate: it `throws` on any failure (route enumeration, permission enumeration, missing frontmatter, duplicate slugs, invalid roles/permissions, unknown route references, unsupported claims, missing inventory entries, etc.) and `exit`s non-zero. It must NEVER succeed silently. If a Node validator test reports "0 routes" or "Route JSON parse failed: Unexpected end of JSON input", the root cause is the validator's child PHP process being blocked by sandbox/network restrictions — re-run from an elevated environment, do not paper over by relaxing the validator. Confidence: 0.85
- The documentation screenshot + validator pipeline (`docs:screenshots` then `docs:validate`) must be run TWICE in succession to prove idempotency: both runs must report `unchanged=12, missing=0, broken=0`, validator `articles=12 errors=0 warnings=0`, and `OK`. A second run that shows any `updated` or `broken` value indicates non-idempotent behavior and must be investigated. Confidence: 0.80
- Documentation golden baselines (PNG references in `tests/visual/baselines/desktop|mobile/`, screenshot manifests, `coverage-screenshots.json`) are immutable during merge-gate work. Merge-gate must NEVER run `npm run ui:update`, must never create new baseline files, and must never modify existing PNG baselines. If a screen is "missing" in baseline verification, report it as a known gap and stop short of declaring PR-ready. Confidence: 0.90

# sidebar-architecture
- Sidebar entries are split into a strict mental model: the main `SidebarContent` holds only role/task navigation items (Dashboard, members, payments, etc.), and the `SidebarFooter` holds utility/help entries (e.g., Pusat Panduan / user guide) placed immediately above the user profile. Each utility entry appears at most once in the entire sidebar — duplicate occurrences across main/admin/member/footer sections are bugs. Footer utility links open same-tab via Inertia `<Link prefetch>` rather than `<a target="_blank">`, so navigation feels like SPA continuity. External links (if any) keep `target="_blank" rel="noopener noreferrer">`. Confidence: 0.85

# docs-content-polish
- When asked to polish user-facing documentation content, do NOT rewrite article bodies wholesale. Instead, produce a per-article maturity audit (grade A/B/C/D, accuracy confidence, missing information, redundant information, technical jargon, potentially unsupported claims, source files to verify, suggested additions/deletions/restructure, unanswered user questions) plus a short editorial-review priority order. Article bodies are only edited after manual editorial review by the user. Confidence: 0.80

# playwright-assertions
- Conditional `if (await someLocator.count()) { ... click/first() ... }` patterns in Playwright tests are forbidden: they cause false positives when the UI state doesn't match the assumption. Replace with deterministic `getByTestId('...')` selectors that target specific elements by stable test-id (e.g., `documentation-screenshot-${id}`, `sidebar-footer-navigation`, `documentation-related-articles`). For "exactly one element" assertions, use `toHaveCount(N)` with the known authoritative count derived from the authorized payload, not a guess. Confidence: 0.85
- When a Playwright test failure shows a count mismatch (`expected 3, got 4` or similar), first verify the actual authorized-element count from the live payload before "fixing" the assertion. If the test's hard-coded count is wrong because it didn't account for additional authorized content, update the assertion; if the extra elements are unintended, that is a real UI bug and must be reported. Never silently weaken assertions to make tests pass. Confidence: 0.80

# communication
- Communicates with the user in Bahasa Indonesia by default (commit subjects, PR/issue text, status reports, explanations). English is only used when the user writes in English first or when technical artifacts (stack traces, API responses, tool output) force it. Confidence: 0.80

# reporting-honesty
- Uses an explicit status vocabulary for task completion: `DONE` means acceptance criteria verified, tests actually executed, branch pushed, PR opened and ready for review. `PARTIAL` means work started but evidence is incomplete. `BLOCKED` means a required access/dependency is missing (list it explicitly, do not silently work around security). `FAILED` means the task cannot be completed as specified. Never claims `DONE` when evidence is missing or assumptions are untested. Confidence: 0.95
- Final report to supervisor uses a fixed structure: TASK (id), STATUS, BRANCH, COMMIT, PR, then sections TEMUAN, PERUBAHAN, VERIFIKASI (with explicit Perintah/skenario and Hasil), RISIKO TERSISA, BLOCKER/AKSES YANG DIBUTUHKAN, REKOMENDASI REVIEWER. Missing sections are filled with explicit `none`/`N/A` rather than being omitted. Confidence: 0.85

# safety-first
- Never deploys to production or runs production migrations/seeds from a hardening task; production-touching actions require explicit stakeholder authorization and are out of scope by default. Confidence: 0.90
- Uses sandbox, fake-provider, or synthetic-data modes for any provider integration test (payment gateway, push notifications, email/SMS). Never exercises real customers, real cards, real recipient phone numbers, or real money in any automated or manual test. Confidence: 0.90
- Redacts secrets, tokens, cookies, credentials, internal URLs, PII, and sensitive payment payloads from logs, screenshots, PR descriptions, and reports. If a value is needed for reproduction it is referenced by placeholder (e.g., `$MIDTRANS_SERVER_KEY`) rather than pasted in. Confidence: 0.90
- Does not bypass security controls to unblock work. If a task needs GitHub Admin, DNS, provider console, staging credentials, or database access that the agent does not have, the task is marked `BLOCKED` with the specific access listed, not silently worked around. Confidence: 0.85

# workflow-discipline
- Before changing code, reads `README`, `AGENTS.md` (if present), relevant CI workflow files, runbooks, and the files directly affected by the task. Writes a short plan before the first edit. Confidence: 0.80
- Only runs the tests, lint, build, and security checks that are actually relevant to the change. Does not skip running tests in order to claim faster turnaround, and never asserts "tests pass" without having executed them and recorded the command and outcome. Confidence: 0.85
- Prefers the smallest change that satisfies the acceptance criteria. Avoids scope creep, opportunistic refactors, formatting churn, and version bumps unrelated to the task. If a tempting unrelated improvement is noticed, it is reported as a follow-up rather than fixed in the same branch. Confidence: 0.85

# branch-naming
- Hardening / production-readiness branches follow the pattern `hardening/<task-id>-<nama-singkat>` (e.g., `hardening/p0-01-remove-public-scripts`). The task id segment must match the `[TASK_ID]` used in the PR title and the supervisor's task list. Confidence: 0.80
