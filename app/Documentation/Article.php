<?php

declare(strict_types=1);

namespace App\Documentation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Read-only value object representing a single user-guide article.
 *
 * Source of truth is the Markdown file under `docs/user-guide/`. The
 * Markdown body is parsed by the loader; the frontmatter is mandatory
 * and validated against the {@see ArticleFrontmatter} shape.
 */
final class Article
{
    public function __construct(
        public readonly ArticleFrontmatter $frontmatter,
        public readonly string $body,
        public readonly string $relativePath,
    ) {}

    /**
     * Parse a Markdown source containing YAML frontmatter delimited by
     * `---` fences. Returns the parsed article or throws.
     *
     * @throws InvalidArticleException
     */
    public static function fromFile(string $absolutePath, string $relativePath): self
    {
        if (! is_file($absolutePath)) {
            throw new InvalidArticleException("Article file not found: {$relativePath}");
        }

        $contents = (string) file_get_contents($absolutePath);

        if (! preg_match('/\A---\s*\R(.*?)\R---\s*\R?(.*)\z/s', $contents, $matches)) {
            throw new InvalidArticleException(
                "Article {$relativePath} is missing YAML frontmatter (--- ... ---).",
            );
        }

        try {
            $parsed = Yaml::parse($matches[1]);
        } catch (ParseException $e) {
            throw new InvalidArticleException(
                "Article {$relativePath} has invalid YAML frontmatter: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (! is_array($parsed)) {
            throw new InvalidArticleException(
                "Article {$relativePath} frontmatter must be a YAML mapping.",
            );
        }

        $frontmatter = ArticleFrontmatter::fromArray($parsed, $relativePath);

        return new self(
            frontmatter: $frontmatter,
            body: rtrim($matches[2]),
            relativePath: $relativePath,
        );
    }

    public function slug(): string
    {
        return $this->frontmatter->slug;
    }

    public function title(): string
    {
        return $this->frontmatter->title;
    }

    public function isPublished(): bool
    {
        return $this->frontmatter->status === 'published';
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->frontmatter->roles;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->frontmatter->permissions;
    }

    public function permissionMode(): string
    {
        return $this->frontmatter->permission_mode;
    }

    public function module(): string
    {
        return $this->frontmatter->module;
    }

    /**
     * @return list<string>
     */
    public function routeNames(): array
    {
        return $this->frontmatter->route_names;
    }

    public function riskLevel(): string
    {
        return $this->frontmatter->risk_level;
    }

    /**
     * @return list<string>
     */
    public function screenshotEntries(): array
    {
        return $this->frontmatter->screenshot_entries;
    }

    /**
     * @return list<string>
     */
    public function relatedArticles(): array
    {
        return $this->frontmatter->related_articles;
    }

    public function lastReviewedCommit(): string
    {
        return $this->frontmatter->last_reviewed_commit;
    }

    public function status(): string
    {
        return $this->frontmatter->status;
    }

    public function summary(): string
    {
        return $this->frontmatter->summary;
    }

    public function category(): string
    {
        return $this->frontmatter->category;
    }

    public function sortOrder(): int
    {
        return $this->frontmatter->sort_order;
    }
}
