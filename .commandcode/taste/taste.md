# Taste (Continuously Learned by [CommandCode][cmd])

[cmd]: https://commandcode.ai/

# vue
- reka-ui SelectItem requires a non-empty string value prop; use sentinel constants (e.g., `__all_statuses__`) instead of empty strings for "all/clear filter" options. Confidence: 0.70

# wayfinder
- When regenerating Wayfinder routes, use `php artisan wayfinder:generate --with-form --no-interaction` to minimize churn in generated files; after generation, revert unrelated generated files to keep diff focused on actual changes. Confidence: 0.70

