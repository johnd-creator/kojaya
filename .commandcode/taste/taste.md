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

