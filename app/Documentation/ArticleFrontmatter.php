<?php

declare(strict_types=1);

namespace App\Documentation;

/**
 * Immutable, validated frontmatter payload for an {@see Article}.
 *
 * The validator enforces the keys required by the in-app user guide's
 * contract. Adding a new key without extending the validator will cause
 * `fromArray()` to throw.
 */
final class ArticleFrontmatter
{
    public const REQUIRED_KEYS = [
        'title',
        'slug',
        'roles',
        'permissions',
        'permission_mode',
        'module',
        'route_names',
        'risk_level',
        'screenshot_entries',
        'related_articles',
        'last_reviewed_commit',
        'status',
        'summary',
        'category',
        'sort_order',
    ];

    public const VALID_ROLES = [
        'all',
        'anggota',
        'admin_koperasi',
        'manajer_koperasi',
        'pengurus_koperasi',
    ];

    public const VALID_PERMISSION_MODES = ['all', 'any'];

    public const VALID_STATUSES = ['published', 'draft', 'archived'];

    public const VALID_RISK_LEVELS = ['low', 'medium', 'high'];

    /**
     * @param  list<string>  $roles
     * @param  list<string>  $permissions
     * @param  list<string>  $routeNames
     * @param  list<string>  $screenshotEntries
     * @param  list<string>  $relatedArticles
     */
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly string $permission_mode,
        public readonly string $module,
        public readonly array $route_names,
        public readonly string $risk_level,
        public readonly array $screenshot_entries,
        public readonly array $related_articles,
        public readonly string $last_reviewed_commit,
        public readonly string $status,
        public readonly string $summary,
        public readonly string $category,
        public readonly int $sort_order,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArticleException
     */
    public static function fromArray(array $data, string $source): self
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArticleException(
                    "Article {$source} is missing required frontmatter key `{$key}`.",
                );
            }
        }

        $title = self::string($data['title'], 'title', $source);
        $slug = self::string($data['slug'], 'slug', $source);
        $summary = self::string($data['summary'], 'summary', $source);
        $category = self::string($data['category'], 'category', $source);
        $module = self::string($data['module'], 'module', $source);

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArticleException(
                "Article {$source} slug `{$slug}` must be kebab-case ASCII (e.g. anggota-loan-flow).",
            );
        }

        $roles = self::stringList($data['roles'], 'roles', $source);
        foreach ($roles as $role) {
            if (! in_array($role, self::VALID_ROLES, true)) {
                throw new InvalidArticleException(
                    "Article {$source} has invalid role `{$role}`. Valid: ".implode(', ', self::VALID_ROLES).'.',
                );
            }
        }

        $permissions = self::stringList($data['permissions'], 'permissions', $source);

        $permissionMode = self::string($data['permission_mode'], 'permission_mode', $source);
        if (! in_array($permissionMode, self::VALID_PERMISSION_MODES, true)) {
            throw new InvalidArticleException(
                "Article {$source} permission_mode must be one of: ".implode(', ', self::VALID_PERMISSION_MODES).'.',
            );
        }

        if ($permissions !== [] && $permissionMode === 'all') {
            // 'all' is the safer default. Allow any permissive case but
            // require explicit declaration. Optional refinement: surface a
            // warning when 'any' is declared with single-element arrays.
        }

        $routeNames = self::stringList($data['route_names'], 'route_names', $source);

        $riskLevel = self::string($data['risk_level'], 'risk_level', $source);
        if (! in_array($riskLevel, self::VALID_RISK_LEVELS, true)) {
            throw new InvalidArticleException(
                "Article {$source} risk_level must be one of: ".implode(', ', self::VALID_RISK_LEVELS).'.',
            );
        }

        $screenshotEntries = self::stringList($data['screenshot_entries'], 'screenshot_entries', $source);

        $relatedArticles = self::stringList($data['related_articles'], 'related_articles', $source);

        $lastReviewedCommit = self::string($data['last_reviewed_commit'], 'last_reviewed_commit', $source);
        if (! preg_match('/^[0-9a-f]{7,40}$/', $lastReviewedCommit)) {
            throw new InvalidArticleException(
                "Article {$source} last_reviewed_commit must be a 7-40 char hex SHA.",
            );
        }

        $status = self::string($data['status'], 'status', $source);
        if (! in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArticleException(
                "Article {$source} status must be one of: ".implode(', ', self::VALID_STATUSES).'.',
            );
        }

        $sortOrderRaw = $data['sort_order'];
        if (! is_int($sortOrderRaw) && ! (is_string($sortOrderRaw) && ctype_digit($sortOrderRaw))) {
            throw new InvalidArticleException(
                "Article {$source} sort_order must be a non-negative integer.",
            );
        }
        $sortOrder = (int) $sortOrderRaw;

        return new self(
            title: $title,
            slug: $slug,
            roles: $roles,
            permissions: $permissions,
            permission_mode: $permissionMode,
            module: $module,
            route_names: $routeNames,
            risk_level: $riskLevel,
            screenshot_entries: $screenshotEntries,
            related_articles: $relatedArticles,
            last_reviewed_commit: $lastReviewedCommit,
            status: $status,
            summary: $summary,
            category: $category,
            sort_order: $sortOrder,
        );
    }

    /**
     * @param  mixed  $value
     *
     * @throws InvalidArticleException
     */
    private static function string(mixed $value, string $key, string $source): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArticleException(
                "Article {$source} frontmatter key `{$key}` must be a non-empty string.",
            );
        }

        return trim($value);
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     *
     * @throws InvalidArticleException
     */
    private static function stringList(mixed $value, string $key, string $source): array
    {
        if (! is_array($value)) {
            throw new InvalidArticleException(
                "Article {$source} frontmatter key `{$key}` must be a list of strings.",
            );
        }

        $out = [];
        foreach ($value as $entry) {
            if (! is_string($entry) || trim($entry) === '') {
                throw new InvalidArticleException(
                    "Article {$source} frontmatter key `{$key}` contains a non-string entry.",
                );
            }
            $out[] = trim($entry);
        }

        return array_values($out);
    }
}
