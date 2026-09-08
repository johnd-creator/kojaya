# Kojaya — Agent Working Agreement

Repository instructions for implementing, reviewing, and maintaining KojayaPro and Kojayaku. Keep this file focused on durable project rules; use linked documentation and skills for detailed procedures.

## Working style and scope

- Respond in concise Indonesian unless the user requests another language. Explain the outcome, relevant evidence, and remaining limitations.
- For implementation requests, continue through discovery, changes, and verification. Make reasonable, reversible implementation choices within scope without repeatedly asking permission.
- For questions, reviews, or diagnosis, inspect and report findings; do not infer permission for unrelated fixes.
- Ask only when a missing decision materially changes business behavior, requires new authority, or makes proceeding unsafe. Complete independent, authorized work first.
- Inspect `git status --short` before editing. Preserve existing user changes; avoid unrelated refactors, formatting, and dependency changes.
- Keep the existing directory structure. Obtain approval before adding dependencies or new top-level folders.
- Do not commit, push, deploy, or send external messages unless authorized by the user.
- Give brief progress updates during sustained work. Report blockers concretely; never claim a check passed unless it ran successfully.

## Read the right context

- Read `docs/project.md`, then relevant sections of `docs/architecture.md` before implementation. Reuse context already read in this session unless it changed.
- Read task-specific documentation before changing behavior:

| Task | Documentation |
| --- | --- |
| API or mobile contract | `docs/api.md` |
| New feature | Relevant sections of `docs/plan.md` and `docs/decisions.md` |
| Existing architectural pattern | Relevant decisions in `docs/decisions.md` |
| Debugging | Relevant recent entries in `docs/log.md` |
| Documentation-only change | Target document and the sources supporting the changed claims |

- Search headings and read relevant sections of large documents. Do not load unrelated history for every small task.
- Documentation records intended behavior; code and tests show implemented behavior. When they disagree, establish which matches the accepted requirement before changing either. Do not automatically assume one is correct.
- Update existing API documentation when contracts change, `docs/decisions.md` for architectural decisions, and `docs/log.md` for significant features. Create new documentation files only when explicitly requested.
- Check instructions in the target directory and existing sibling implementations before editing.

## Code discovery and evidence

- Prefer codebase-memory-mcp for structural code discovery. At session start or after compaction, confirm the nearest project and generation with `list_projects` or `index_status`.
- Default to Tier 2 verification: `search_graph` for symbols, `trace_path` for relevant callers/callees, and `get_code_snippet` for material implementation details. Use `query_graph` or `get_architecture` for broader relationships.
- Once candidate paths are known, call `check_index_coverage` with every evidence path. Include bounded scopes for negative or exhaustive claims and complete relevant pagination.
- Clean coverage means no recorded gap, not proof of completeness. For partial, skipped, excluded, stale, pending, or unknown coverage, read the source and reported missed ranges before relying on results.
- Use `rg` for literals, errors, configuration, non-code files, and source verification. If graph tools are unavailable or insufficient, use focused source searches and disclose the limitation.
- Tier 1 scout results are provisional positive findings. Tier 3 audits require complete relevant pagination, freshness checks, both call directions where material, and disclosed limitations.
- If delegation is authorized, first collect parent graph and coverage evidence. Assign bounded ownership and pass project/generation, tier, symbols, paths, pagination, missed ranges, source checks, and unresolved questions. Agents must preserve others' changes and must not claim unavailable MCP access.

## Project and domain invariants

- KojayaPro is the cooperative ERP/POS backend and staff web application; Kojayaku serves members.
- Stack: Laravel 12, PHP, PostgreSQL, Inertia v2, Vue 3 with TypeScript, Tailwind CSS v4, Wayfinder, and PHPUnit.
- Use `composer.json` / `package.json` for supported constraints, lockfiles for resolved versions, and installed tools for actual runtime versions. Do not rely on hard-coded patch versions in this file.
- Related mobile app: native Android with Kotlin at `/home/john-d/Videos/KojayaApp`. This replaces the previous Flutter app as the mobile integration reference. When changing shared API or member-facing behavior, read that repository's applicable instructions and inspect the relevant Kotlin flow first; align endpoints, menu names, payload fields, and screen expectations. If unavailable, disclose the compatibility gap.
- Laravel is authoritative for persistence, authorization, validation, and accounting/POS side effects. A mobile prototype does not establish backend behavior or authorize editing that separate repository. Older documentation referring to Flutter is historical; use the current Kotlin app for mobile compatibility checks.
- Global highest role: `System Admin`. Cooperative hierarchy: `Pengurus Koperasi` → `Manajer Koperasi` → `Admin Koperasi` → operational roles such as `Kasir Koperasi`.
- Loan approval requires manager review first and final Pengurus approval. Admin Koperasi manages operational loan data but is not a loan approver.
- Preserve organization isolation and object/parent ownership checks. Reuse the canonical organization scoping described in `docs/architecture.md`; role hierarchy alone does not grant cross-organization access.
- For financial/POS changes, verify affected balances, inventory, transaction boundaries, duplicate/retry behavior, and audit records. Keep business side effects in the established service flow.

## Database and secret safety

- The shared local database `kojaya_erp` contains valuable demo, login, role, permission, and QA data. Never reset or broadly reseed it without explicit approval in the current conversation.
- Forbidden against the shared database without that approval: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `db:seed`, broad seeders, dropping/truncating tables or schemas, deleting all rows, and bulk rewriting login/role/permission data.
- Apply only new forward migrations with `php artisan migrate`. First confirm the intended connection/database and inspect `php artisan migrate:status`; review pending migrations for destructive operations.
- Before one-off recovery, identify the exact database, tables, users, seeders, and effects, then wait for explicit approval.
- Tests must use an isolated testing database. Inspect the selected PHPUnit configuration and effective connection before database traits or migrations run. `APP_ENV=testing` alone is not proof of isolation.
- Default `phpunit.xml` configures SQLite `:memory:`. Alternate suites and browser tests need their own verified isolation. Never redirect them to `kojaya_erp` to make tests pass.
- Use Laravel testing traits, factories, and per-test fixtures. Do not manually reset databases, run broad seeders, or truncate tables to repair test failures.
- If a destructive command runs accidentally, stop and report the command, timestamp, database, and observed damage. Do not attempt further resets or recovery without approval.
- Never dump `.env` or expose credentials, tokens, payment keys, or private member data in outputs, source, tests, or documentation.
- Midtrans uses `services.midtrans` in `config/services.php`. Local testing uses `MIDTRANS_IS_PRODUCTION=false`; inspect only necessary non-secret settings. Keep `MIDTRANS_SERVER_KEY` in local environment/deployment secrets and use fake values in tests.
- Inspect package scripts before running setup commands: they may generate keys, run migrations, seed data, or start workers with side effects.

## Tools and skills

- Use Laravel Boost when available. Before Laravel/Inertia/Tailwind code changes, use `search-docs` with broad topic queries and relevant package filters for version-matched guidance.
- If Boost is unavailable, use installed source and official documentation matching installed versions. State the limitation without blocking work that can be verified safely.
- Use `list-artisan-commands` to check command options; fall back to command help. Generate Laravel classes with `php artisan make:*` and `--no-interaction`.
- Use `database-schema` before schema/model changes and `database-query` for read-only queries. Use `tinker` only when Eloquent/runtime inspection is needed; prefer existing tests when they cover the question.
- Use recent `browser-logs` for browser failures. Resolve shared project URLs with `get-absolute-url`; if unavailable, verify the running server/configuration rather than guessing.
- Read and apply relevant skills before domain work: `inertia-vue-development` for Inertia Vue pages/forms/navigation, `tailwindcss-development` for styling, and `wayfinder-development` for backend routes in frontend code. Announce first use.
- Do not load unrelated skills or perform broad audits solely because tools are available.

## Implementation conventions

### Laravel and PHP

- Follow sibling conventions and reuse services, components, factories, and request classes before adding abstractions.
- Use descriptive names, explicit parameter/return types, curly braces, and constructor property promotion where appropriate. Avoid empty public constructors.
- Prefer useful PHPDoc types, including array shapes; comment non-obvious intent rather than narrating code. Follow existing enum naming, normally TitleCase.
- Use Form Requests with validation rules and custom messages; keep validation and business side effects out of controllers where existing services own them.
- Use Eloquent relationships with return types, eager loading, and appropriate pagination. Prefer model queries; use query builder or transactions where required by the operation.
- Use Laravel authorization, policies/gates, and Sanctum consistently with existing organization scoping.
- Keep API versioning and API Resources consistent with existing contracts. Generate links from named routes.
- Read environment variables only in configuration files; use `config()` in application code.
- Use existing queue patterns and `ShouldQueue` for expensive background work; preserve transaction/dispatch ordering.
- In Laravel 12, register middleware/exceptions/routing in `bootstrap/app.php` and providers in `bootstrap/providers.php`. Follow sibling model cast conventions.
- Column modifications must preserve existing attributes that should remain. Create useful factories for new models; add seeders only when the feature actually needs them.

### Vue, Inertia, and Tailwind

- Use existing Vue 3/TypeScript conventions and a single root element. Pages live in `resources/js/pages`; render them with `Inertia::render()`.
- Reuse existing UI components and Tailwind v4 patterns. Handle relevant loading, empty, validation, and error states; deferred props need an appropriate loading placeholder.
- Use Wayfinder imports from `@/actions` or `@/routes`, preserving binding and query semantics. Use supported Inertia form helpers.
- Regenerate generated route bindings using the project's workflow; do not hand-edit generated files.
- Verify changed UI flows at relevant viewport sizes and roles, including accessibility and browser errors where applicable.
- If frontend changes are not visible, inspect the Vite server/build state. Run the appropriate scoped build or explain the exact missing prerequisite.

## Verification and completion

- Select checks by affected behavior and risk. Fixes and behavior changes require meaningful regression coverage; include relevant success, validation, authorization, and edge cases.
- Write PHPUnit classes for new PHP tests. Use `php artisan make:test --phpunit --no-interaction` with `--unit` only for isolated unit tests. Do not convert unrelated tests or delete tests without approval.
- Run every changed test and the smallest affected suite, for example `php artisan test --compact tests/Feature/ExampleTest.php` or `php artisan test --compact --filter=testName`.
- Formatting-only and documentation-only changes need appropriate formatting, link/content checks, and diff review; do not create artificial application tests for prose.
- After PHP edits, run `vendor/bin/pint --dirty --format agent`. In a dirty worktree, ensure formatting does not overwrite unrelated user changes.
- For frontend changes, run targeted ESLint/Prettier checks, type checking, a build, or relevant Playwright tests as appropriate. Inspect `package.json` first: `npm run lint` applies fixes across the repository and `npm run format` writes throughout `resources/`.
- Do not refresh visual baselines merely to hide a failing comparison; inspect and explain intended visual differences.
- Inspect suite exclusions and alternate test configurations before claiming broad coverage. A default PHPUnit pass does not include every test in the repository.
- Broaden testing for shared infrastructure, high-risk side effects, unresolved failures, or a user request. Do not rerun passing checks without a reason.
- Finish by reviewing `git diff --check` and the actual diff for unintended changes. Report what changed, checks run/results, and material unverified behavior. If blocked, distinguish completed work from the exact remaining dependency.

## Maintaining this file

- Update this file when explicitly requested or when an authorized task changes a documented repository rule. Prefer a small correction over accumulating instructions.
- Keep durable project constraints here; keep task progress, temporary workarounds, secrets, and changing package inventories elsewhere.
- Remove duplication and resolve conflicting rules without weakening database safety, authorization, or verification.
- When regenerating Laravel Boost guidance, review the diff and preserve these project-specific rules.
