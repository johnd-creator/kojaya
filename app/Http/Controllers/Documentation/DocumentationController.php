<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documentation;

use App\Documentation\Article;
use App\Documentation\ArticleAuthorizer;
use App\Documentation\ArticleRepository;
use App\Documentation\ContextualHelpRegistry;
use App\Documentation\DocumentationRoleResolver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app user guide controller.
 *
 * The documentation center is backed by a file-based repository
 * ({@see ArticleRepository}) — no database table stores article
 * content. Every authorization decision is delegated to
 * {@see ArticleAuthorizer}, which uses the same `PrimaryRoleResolver`
 * and Spatie permission set as the rest of the application.
 */
final class DocumentationController extends Controller
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly ArticleAuthorizer $authorizer,
        private readonly ContextualHelpRegistry $contextualHelp,
        private readonly DocumentationRoleResolver $docRoleResolver,
    ) {}

    /**
     * Display the documentation center landing page filtered to the
     * current user's roles and permissions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->abortIfGuest($user);

        $visible = $this->authorizer->filterVisible($user);
        $docRole = $this->docRoleResolver->resolve($user);

        return Inertia::render('Documentation/Index', [
            'sections' => $this->groupSections($visible),
            'modules' => $this->collectModules($visible),
            'articles' => $this->serializeList($visible),
            'userRoles' => $user->getRoleNames()->values()->all(),
            'primaryRole' => $docRole,
            'searchEnabled' => true,
        ]);
    }

    /**
     * Display a single article.
     */
    public function show(Request $request, string $slug): Response
    {
        $user = $request->user();
        $this->abortIfGuest($user);

        $article = $this->articles->findBySlug($slug);
        if ($article === null) {
            abort(404);
        }

        if (! $this->authorizer->canView($user, $article)) {
            abort(403);
        }

        $previous = $this->findNeighbour($article, -1, $user);
        $next = $this->findNeighbour($article, +1, $user);
        $related = $this->findRelated($article, $user);
        $siblings = $this->collectSiblings($article, $user);
        $docRole = $this->docRoleResolver->resolve($user);

        return Inertia::render('Documentation/Show', [
            'article' => $this->serializeArticle($article),
            'navigation' => [
                'previous' => $previous === null ? null : $this->serializeNavItem($previous),
                'next' => $next === null ? null : $this->serializeNavItem($next),
                'related' => $related,
            ],
            'siblings' => $siblings,
            'contextualHelp' => $this->contextualHelp->forRole($docRole),
            'primaryRole' => $docRole,
        ]);
    }

    private function abortIfGuest(?User $user): void
    {
        abort_unless($user !== null, 401);
    }

    /**
     * @param  Collection<int, Article>  $articles
     * @return list<array{category: string, articles: list<array<string, mixed>>}>
     */
    private function groupSections(Collection $articles): array
    {
        return $articles
            ->groupBy(fn (Article $a): string => $a->category())
            ->map(function (Collection $group, string $category): array {
                $items = $group
                    ->sortBy('sort_order')
                    ->map(fn (Article $a): array => $this->serializeListItem($a))
                    ->values()
                    ->all();

                return [
                    'category' => $category,
                    'articles' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Article>  $articles
     * @return list<string>
     */
    private function collectModules(Collection $articles): array
    {
        return $articles
            ->map(fn (Article $a): string => $a->module())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Article>  $articles
     * @return list<array<string, mixed>>
     */
    private function serializeList(Collection $articles): array
    {
        return $articles
            ->sortBy('sort_order')
            ->map(fn (Article $a): array => $this->serializeListItem($a))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListItem(Article $article): array
    {
        return [
            'slug' => $article->slug(),
            'title' => $article->title(),
            'summary' => $article->summary(),
            'category' => $article->category(),
            'module' => $article->module(),
            'roles' => $article->roles(),
            'permissions' => $article->permissions(),
            'permission_mode' => $article->permissionMode(),
            'risk_level' => $article->riskLevel(),
            'screenshot_entries' => $article->screenshotEntries(),
            'related_articles' => $article->relatedArticles(),
            'last_reviewed_commit' => $article->lastReviewedCommit(),
            'status' => $article->status(),
            'sort_order' => $article->sortOrder(),
            'search_text' => $article->searchText(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeArticle(Article $article): array
    {
        $list = $this->serializeListItem($article);
        $list['body'] = $article->body;

        return $list;
    }

    private function findNeighbour(Article $article, int $offset, User $user): ?Article
    {
        $list = $this->authorizer
            ->filterVisible($user)
            ->sortBy([
                ['sort_order', 'asc'],
                ['category', 'asc'],
                ['title', 'asc'],
            ])
            ->values();

        $index = null;
        foreach ($list as $i => $candidate) {
            if ($candidate->slug() === $article->slug()) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return null;
        }
        $target = $index + $offset;
        if ($target < 0 || $target >= $list->count()) {
            return null;
        }

        return $list->get($target);
    }

    /**
     * @return list<array{slug: string, title: string, category: string, summary: string}>
     */
    private function findRelated(Article $article, User $user): array
    {
        $related = $article->relatedArticles();
        if ($related === []) {
            return [];
        }

        $visible = $this->authorizer->filterVisible($user);
        $visibleBySlug = [];
        foreach ($visible as $candidate) {
            $visibleBySlug[$candidate->slug()] = $candidate;
        }

        $out = [];
        foreach ($related as $slug) {
            if (! isset($visibleBySlug[$slug])) {
                continue;
            }
            $out[] = $this->serializeNavItem($visibleBySlug[$slug]);
        }

        return $out;
    }

    /**
     * @return array{slug: string, title: string, category: string, summary: string}
     */
    private function serializeNavItem(Article $article): array
    {
        return [
            'slug' => $article->slug(),
            'title' => $article->title(),
            'category' => $article->category(),
            'summary' => $article->summary(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectSiblings(Article $article, User $user): array
    {
        return $this->authorizer
            ->filterVisible($user)
            ->filter(fn (Article $a): bool => $a->category() === $article->category() && $a->slug() !== $article->slug())
            ->sortBy('sort_order')
            ->map(fn (Article $a): array => $this->serializeListItem($a))
            ->values()
            ->all();
    }
}
