<?php

declare(strict_types=1);

namespace App\Documentation;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

/**
 * Read-only repository for in-app user guide articles.
 *
 * Articles live as Markdown files under `docs/user-guide/`. Each file
 * must start with a YAML frontmatter block describing the article's
 * target role, required permissions, related routes, and so on. The
 * repository is the only source consulted at runtime; no database
 * table backs the documentation center.
 *
 * Results are memoized per-request via a small in-memory cache and can
 * be cached across requests through Laravel's cache when the caller
 * passes an instance via {@see self::setCache()}.
 */
final class ArticleRepository
{
    /** @var array<string, Article>|null */
    private ?array $articles = null;

    private ?CacheRepository $cache = null;

    private ?int $cacheTtl = null;

    public function __construct(
        private readonly string $basePath,
    ) {}

    public function setCache(CacheRepository $cache, int $ttlSeconds = 3600): void
    {
        $this->cache = $cache;
        $this->cacheTtl = $ttlSeconds;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return Collection<int, Article>
     */
    public function all(): Collection
    {
        return collect($this->loadAll());
    }

    /**
     * @return Collection<int, Article>
     */
    public function published(): Collection
    {
        return $this->all()->filter(fn (Article $a): bool => $a->isPublished())->values();
    }

    public function findBySlug(string $slug): ?Article
    {
        return $this->loadAll()[$slug] ?? null;
    }

    /**
     * Filter articles to those visible to the given user.
     *
     * Delegates to {@see ArticleAuthorizer} so the role/permission
     * rules stay in one place. The single source of truth is the
     * documentation role bucket, not a Spatie role union.
     *
     * @return Collection<int, Article>
     */
    public function visibleTo(\App\Models\User $user): Collection
    {
        $authorizer = app(ArticleAuthorizer::class);

        return $authorizer->filterVisible($user);
    }

    /**
     * Look up a documentation target role from a list of Spatie role
     * names. Kept for diagnostic use only — the authorizer no longer
     * consumes this list directly. Uses exact-name matching, never
     * substring, so a `Site Manager` cannot leak into
     * `manajer_koperasi`.
     *
     * @param  list<string>  $spatieRoleNames
     * @return list<string>
     */
    public function resolveTargetRoles(array $spatieRoleNames): array
    {
        $map = [
            'System Admin' => 'system_admin',
            'Admin Pusat' => 'admin_pusat',
            'Pengurus Koperasi' => 'pengurus_koperasi',
            'Manajer Koperasi' => 'manajer_koperasi',
            'Admin Koperasi' => 'admin_koperasi',
            'Kasir Koperasi' => 'admin_koperasi',
            'Anggota' => 'anggota',
        ];

        $targets = ['all'];
        foreach ($spatieRoleNames as $name) {
            if (isset($map[$name]) && ! in_array($map[$name], $targets, true)) {
                $targets[] = $map[$name];
            }
        }

        return $targets;
    }

    /**
     * @return array<string, Article> slug => article
     */
    public function loadAll(): array
    {
        if ($this->articles !== null) {
            return $this->articles;
        }

        $cacheKey = $this->cacheKey();

        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                $this->articles = $this->hydrateCache($cached);

                return $this->articles;
            }
        }

        if (! is_dir($this->basePath)) {
            $this->articles = [];

            return $this->articles;
        }

        $loaded = [];

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($rii as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }
            $absolute = $file->getPathname();
            $relative = ltrim(
                substr($absolute, strlen($this->basePath)),
                DIRECTORY_SEPARATOR,
            );

            // Skip the audit/correction report and the directory-level README.
            $basename = basename($relative);
            if (in_array($basename, ['README.md', 'content-correction-audit.md'], true)) {
                continue;
            }

            $contents = (string) file_get_contents($absolute);
            if (! str_starts_with($contents, '---')) {
                // Skip non-article Markdown files (inventory, README, audit, etc.).
                continue;
            }

            try {
                $article = Article::fromFile($absolute, $relative);
            } catch (InvalidArticleException $e) {
                // Re-throw so callers (validator, tests) see a hard error.
                throw $e;
            }

            $loaded[$article->slug()] = $article;
        }

        $this->articles = $loaded;

        if ($this->cache !== null && $this->cacheTtl !== null) {
            $this->cache->put($cacheKey, $this->serializeForCache($loaded), $this->cacheTtl);
        }

        return $this->articles;
    }

    /**
     * Reset the in-memory cache. Used by tests and the validator when the
     * underlying Markdown files may have changed.
     */
    public function flush(): void
    {
        $this->articles = null;
        if ($this->cache !== null) {
            $this->cache->forget($this->cacheKey());
        }
    }

    private function cacheKey(): string
    {
        $pathHash = substr(md5($this->basePath), 0, 8);

        return "documentation:articles:{$pathHash}";
    }

    /**
     * @param  array<string, Article>  $articles
     * @return array<string, array{frontmatter: array<string, mixed>, body: string, relativePath: string}>
     */
    private function serializeForCache(array $articles): array
    {
        $out = [];
        foreach ($articles as $slug => $article) {
            $out[$slug] = [
                'frontmatter' => [
                    'title' => $article->frontmatter->title,
                    'slug' => $article->frontmatter->slug,
                    'roles' => $article->frontmatter->roles,
                    'permissions' => $article->frontmatter->permissions,
                    'permission_mode' => $article->frontmatter->permission_mode,
                    'module' => $article->frontmatter->module,
                    'route_names' => $article->frontmatter->route_names,
                    'risk_level' => $article->frontmatter->risk_level,
                    'screenshot_entries' => $article->frontmatter->screenshot_entries,
                    'related_articles' => $article->frontmatter->related_articles,
                    'last_reviewed_commit' => $article->frontmatter->last_reviewed_commit,
                    'status' => $article->frontmatter->status,
                    'summary' => $article->frontmatter->summary,
                    'category' => $article->frontmatter->category,
                    'sort_order' => $article->frontmatter->sort_order,
                ],
                'body' => $article->body,
                'relativePath' => $article->relativePath,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{frontmatter: array<string, mixed>, body: string, relativePath: string}>  $cached
     * @return array<string, Article>
     */
    private function hydrateCache(array $cached): array
    {
        $out = [];
        foreach ($cached as $slug => $entry) {
            $frontmatter = ArticleFrontmatter::fromArray($entry['frontmatter'], $entry['relativePath']);
            $out[$slug] = new Article(
                frontmatter: $frontmatter,
                body: $entry['body'],
                relativePath: $entry['relativePath'],
            );
        }

        return $out;
    }
}
