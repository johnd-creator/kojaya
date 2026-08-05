<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Models\User;

/**
 * Central registry of contextual help mappings.
 *
 * The mapping shape is fixed in the in-app user guide contract:
 *
 *   route name  →  documentation slug  →  role  →  permission  →  screenshot state
 *
 * The source of truth is `resources/docs/user-guide/contextual-help.json`.
 * Both the backend (this class) and the docs:validate Node script read
 * from that JSON so the validator and the runtime cannot drift.
 *
 * Per Fase 11, every entry in the JSON file is also validated
 * server-side: route name, slug, role, permission, and screenshot
 * state are all checked against the live application state.
 */
final class ContextualHelpRegistry
{
    /**
     * @var list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>|null
     */
    private ?array $entries = null;

    /**
     * @var list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    private array $duplicates = [];

    public function __construct(
        private readonly string $jsonPath,
        private readonly ArticleRepository $articles,
        private readonly ArticleAuthorizer $authorizer,
    ) {}

    /**
     * @return list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    public function all(): array
    {
        $this->load();

        return $this->entries ?? [];
    }

    public function duplicates(): array
    {
        $this->load();

        return $this->duplicates;
    }

    /**
     * Resolve the contextual help entry for the current request.
     *
     * The entry is only returned if EVERY of the following holds:
     *
     *  - there is an entry for the given route name;
     *  - the entry's `role` matches the user's primary/effective
     *    documentation role (or System Admin for any role);
     *  - the user has the required permission (if any);
     *  - the referenced article exists, is published, and the user
     *    is allowed to read it.
     *
     * Returns `null` if any check fails — the shared layout's
     * "Lihat Panduan" button will then hide itself.
     *
     * @return array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     *     article: array<string, mixed>,
     * }|null
     */
    public function resolveForRequest(
        string $routeName,
        User $user,
        DocumentationRoleResolver $roleResolver,
    ): ?array {
        $this->load();

        $docRole = $roleResolver->resolve($user);
        $candidates = array_values(array_filter(
            $this->entries ?? [],
            static fn (array $e): bool => $e['route'] === $routeName,
        ));

        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $entry) {
            if ($docRole === DocumentationRoleResolver::ROLE_SYSTEM_ADMIN) {
                // System Admin sees every entry; just confirm the
                // article exists and is published.
                $article = $this->articles->findBySlug($entry['slug']);
                if ($article === null || ! $article->isPublished()) {
                    continue;
                }

                return $this->enrich($entry, $article);
            }

            if ($entry['role'] !== $docRole && $entry['role'] !== 'all' && $entry['role'] !== 'shared') {
                continue;
            }

            if (isset($entry['permission']) && ! $user->can($entry['permission'])) {
                continue;
            }

            $article = $this->articles->findBySlug($entry['slug']);
            if ($article === null || ! $article->isPublished()) {
                continue;
            }

            if (! $this->authorizer->canView($user, $article)) {
                continue;
            }

            return $this->enrich($entry, $article);
        }

        return null;
    }

    /**
     * @return list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    public function forRole(string $docRole): array
    {
        $this->load();

        $resolved = $docRole === DocumentationRoleResolver::ROLE_SYSTEM_ADMIN
            ? ($this->entries ?? [])
            : array_values(array_filter(
                $this->entries ?? [],
                static fn (array $e): bool => $e['role'] === $docRole || $e['role'] === 'all' || $e['role'] === 'shared',
            ));

        return $resolved;
    }

    /**
     * @param  array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }  $entry
     * @return array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     *     article: array<string, mixed>,
     * }
     */
    private function enrich(array $entry, Article $article): array
    {
        return $entry + [
            'article' => [
                'slug' => $article->slug(),
                'title' => $article->title(),
                'summary' => $article->summary(),
                'category' => $article->category(),
                'module' => $article->module(),
            ],
        ];
    }

    private function load(): void
    {
        if ($this->entries !== null) {
            return;
        }

        if (! is_file($this->jsonPath)) {
            $this->entries = [];

            return;
        }

        $raw = file_get_contents($this->jsonPath);
        if ($raw === false) {
            $this->entries = [];

            return;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['entries']) || ! is_array($decoded['entries'])) {
            $this->entries = [];

            return;
        }

        $seen = [];
        $entries = [];
        foreach ($decoded['entries'] as $row) {
            if (! is_array($row) || ! isset($row['route'], $row['slug'], $row['role'], $row['screenshot_state'], $row['label'])) {
                continue;
            }
            $key = $row['route'].'|'.$row['role'];
            if (isset($seen[$key])) {
                $this->duplicates[] = $row;

                continue;
            }
            $seen[$key] = true;
            $entries[] = [
                'route' => (string) $row['route'],
                'slug' => (string) $row['slug'],
                'role' => (string) $row['role'],
                'screenshot_state' => (string) $row['screenshot_state'],
                'label' => (string) $row['label'],
                'permission' => isset($row['permission']) ? (string) $row['permission'] : null,
            ];
        }

        $this->entries = $entries;
    }
}
