<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Display the documentation center landing page filtered to the current
     * user's roles and permissions. The filtering happens in the
     * `visibleTo` scope; the frontend never receives articles the
     * authenticated user is not allowed to read.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentationArticle::class);

        $user = $request->user();

        $articles = DocumentationArticle::query()
            ->visibleTo(
                $user->getRoleNames()->all(),
                $user->getAllPermissions()->pluck('name')->all(),
            )
            ->get()
            ->groupBy('category')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'articles' => $group->map(fn (DocumentationArticle $article) => $article->toInertia())->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Documentation/Index', [
            'sections' => $articles,
            'userRoles' => $user->getRoleNames()->values()->all(),
        ]);
    }

    /**
     * Display a single article. The policy is the single source of truth
     * for whether the current user may read the article; the controller
     * delegates via `authorize()` and returns 403 when not allowed.
     */
    public function show(Request $request, DocumentationArticle $article): Response
    {
        $this->authorize('view', $article);

        return Inertia::render('Documentation/Show', [
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'summary' => $article->summary,
                'category' => $article->category,
                'target_role' => $article->target_role,
                'body_markdown' => $article->body_markdown,
            ],
        ]);
    }
}
