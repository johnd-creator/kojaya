<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Documentation\ArticleRepository;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewDocumentationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_documentation_center(): void
    {
        $this->get('/documentation')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_the_documentation_center(): void
    {
        $user = $this->makeUserWithRole('Anggota');

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Documentation/Index')
            ->has('sections')
            ->where('userRoles.0', 'Anggota'),
        );
    }

    public function test_anggota_only_sees_anggota_or_all_articles(): void
    {
        $anggota = $this->makeUserWithRole('Anggota');

        $response = $this->actingAs($anggota)
            ->get('/documentation')
            ->assertOk();

        $roles = $this->collectTargetRoles($response);
        foreach ($roles as $role) {
            $this->assertContains(
                $role,
                ['all', 'anggota'],
                "Anggota must not see an article with target_role={$role}",
            );
        }
    }

    public function test_admin_koperasi_sees_only_admin_or_all_articles(): void
    {
        $admin = $this->makeUserWithRole('Admin Koperasi');

        $response = $this->actingAs($admin)
            ->get('/documentation')
            ->assertOk();

        foreach ($this->collectTargetRoles($response) as $role) {
            $this->assertContains(
                $role,
                ['all', 'admin_koperasi'],
                "Admin Koperasi must not see an article with target_role={$role}",
            );
        }
    }

    public function test_manajer_koperasi_sees_only_manajer_or_all_articles(): void
    {
        $manajer = $this->makeUserWithRole('Manajer Koperasi');

        $response = $this->actingAs($manajer)
            ->get('/documentation')
            ->assertOk();

        foreach ($this->collectTargetRoles($response) as $role) {
            $this->assertContains(
                $role,
                ['all', 'manajer_koperasi'],
                "Manajer must not see an article with target_role={$role}",
            );
        }
    }

    public function test_pengurus_koperasi_sees_only_pengurus_or_all_articles(): void
    {
        $pengurus = $this->makeUserWithRole('Pengurus Koperasi');

        $response = $this->actingAs($pengurus)
            ->get('/documentation')
            ->assertOk();

        foreach ($this->collectTargetRoles($response) as $role) {
            $this->assertContains(
                $role,
                ['all', 'pengurus_koperasi'],
                "Pengurus must not see an article with target_role={$role}",
            );
        }
    }

    public function test_show_route_returns_403_when_user_lacks_target_role(): void
    {
        $anggota = $this->makeUserWithRole('Anggota');

        $this->actingAs($anggota)
            ->get('/documentation/pengurus-loan-approval')
            ->assertForbidden();
    }

    public function test_show_route_returns_200_for_user_with_required_role(): void
    {
        $pengurus = $this->makeUserWithRole('Pengurus Koperasi');

        $this->actingAs($pengurus)
            ->get('/documentation/pengurus-loan-approval')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Documentation/Show')
                ->where('article.slug', 'pengurus-loan-approval'),
            );
    }

    public function test_show_route_returns_404_for_unknown_slug(): void
    {
        $user = $this->makeUserWithRole('Anggota');

        $this->actingAs($user)
            ->get('/documentation/this-slug-does-not-exist')
            ->assertNotFound();
    }

    public function test_documentation_routes_require_authentication(): void
    {
        $this->get('/documentation')->assertRedirect('/login');

        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();
    }

    public function test_documentation_routes_have_verified_middleware_declared(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(static fn ($route) => str_starts_with((string) $route->getName(), 'documentation.'))
            ->all();

        $this->assertNotEmpty($routes, 'Documentation routes are not registered.');

        foreach ($routes as $route) {
            $middleware = $route->middleware();
            $this->assertContains('auth', $middleware, 'Route '.$route->getName().' must use `auth` middleware.');
            $this->assertContains('verified', $middleware, 'Route '.$route->getName().' must use `verified` middleware.');
        }
    }

    public function test_article_with_required_permission_is_hidden_from_user_without_it(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_a', 'guard_name' => 'web']);

        $user = $this->makeUserWithRole('Admin Koperasi');

        // Create a draft article with `custom_doc_perm_a` and verify it is hidden.
        $slug = 'hidden-test-'.strtolower(Str::random(6));
        $path = base_path('docs/user-guide/admin-koperasi/'.$slug.'.md');
        @mkdir(dirname($path), 0775, true);
        file_put_contents(
            $path,
            "---\n".
            "title: Hidden Test\n".
            "slug: {$slug}\n".
            "summary: Hidden by permission gate.\n".
            "category: Test\n".
            "module: test\n".
            "roles: [admin_koperasi]\n".
            "permissions: [custom_doc_perm_a]\n".
            "permission_mode: all\n".
            "route_names: []\n".
            "risk_level: low\n".
            "screenshot_entries: []\n".
            "related_articles: []\n".
            "last_reviewed_commit: 20c86960\n".
            "status: published\n".
            "sort_order: 999\n".
            "---\n\n# Hi\n",
        );
        app(\App\Documentation\ArticleRepository::class)->flush();

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();
        $slugs = $this->collectSlugs($response);
        $this->assertNotContains(
            $slug,
            $slugs,
            'User without custom_doc_perm_a must not see the article.',
        );

        $user->givePermissionTo('custom_doc_perm_a');
        app(\App\Documentation\ArticleRepository::class)->flush();
        $response = $this->actingAs($user)->get('/documentation')->assertOk();
        $this->assertContains($slug, $this->collectSlugs($response));

        @unlink($path);
        app(\App\Documentation\ArticleRepository::class)->flush();
    }

    public function test_article_with_required_permission_is_shown_to_user_with_at_least_one_match(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $user = $this->makeUserWithRole('Admin Koperasi');
        $user->givePermissionTo('custom_doc_perm_b');

        $slug = 'visible-test-'.strtolower(Str::random(6));
        $path = base_path('docs/user-guide/admin-koperasi/'.$slug.'.md');
        @mkdir(dirname($path), 0775, true);
        file_put_contents(
            $path,
            "---\n".
            "title: Visible Test\n".
            "slug: {$slug}\n".
            "summary: Visible because user has at least one permission.\n".
            "category: Test\n".
            "module: test\n".
            "roles: [admin_koperasi]\n".
            "permissions: [custom_doc_perm_b]\n".
            "permission_mode: all\n".
            "route_names: []\n".
            "risk_level: low\n".
            "screenshot_entries: []\n".
            "related_articles: []\n".
            "last_reviewed_commit: 20c86960\n".
            "status: published\n".
            "sort_order: 999\n".
            "---\n\n# Hi\n",
        );
        app(\App\Documentation\ArticleRepository::class)->flush();

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();
        $this->assertContains($slug, $this->collectSlugs($response));

        @unlink($path);
        app(\App\Documentation\ArticleRepository::class)->flush();
    }

    public function test_permission_mode_any_returns_article_when_user_has_one_match(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_a', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $article = $this->createArticleWithMode(
            ['custom_doc_perm_a', 'custom_doc_perm_b'],
            'any',
        );
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $user->givePermissionTo('custom_doc_perm_a');

        $this->actingAs($user)
            ->get('/documentation/'.$article->slug())
            ->assertOk();
    }

    public function test_permission_mode_all_requires_every_permission(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_a', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $article = $this->createArticleWithMode(
            ['custom_doc_perm_a', 'custom_doc_perm_b'],
            'all',
        );

        $partial = User::factory()->create();
        $partial->assignRole('Admin Koperasi');
        $partial->givePermissionTo('custom_doc_perm_a');

        app(\App\Documentation\ArticleRepository::class)->flush();
        $this->actingAs($partial)
            ->get('/documentation/'.$article->slug())
            ->assertForbidden();

        $full = User::factory()->create();
        $full->assignRole('Admin Koperasi');
        $full->givePermissionTo(['custom_doc_perm_a', 'custom_doc_perm_b']);

        app(\App\Documentation\ArticleRepository::class)->flush();
        $this->actingAs($full)
            ->get('/documentation/'.$article->slug())
            ->assertOk();
    }

    public function test_draft_article_is_hidden_from_index_and_show(): void
    {
        $draft = $this->createArticleWithMode([], 'all', draft: true);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();
        $this->assertNotContains($draft->slug(), $this->collectSlugs($response));

        $this->actingAs($user)
            ->get('/documentation/'.$draft->slug())
            ->assertForbidden();
    }

    public function test_invalid_role_in_frontmatter_is_rejected_by_repository(): void
    {
        $relative = 'anggota/bad-roles-'.Str::random(6).'.md';
        $path = base_path('docs/user-guide/'.$relative);
        @mkdir(dirname($path), 0775, true);
        file_put_contents(
            $path,
            "---\n".
            "title: Bad\n".
            'slug: bad-roles-'.Str::random(6)."\n".
            "summary: Bad frontmatter test\n".
            "category: Test\n".
            "module: test\n".
            "roles: [not_a_role]\n".
            "permissions: []\n".
            "permission_mode: all\n".
            "route_names: []\n".
            "risk_level: low\n".
            "screenshot_entries: []\n".
            "related_articles: []\n".
            "last_reviewed_commit: 20c86960\n".
            "status: published\n".
            "sort_order: 999\n".
            "---\n\n# Hi\n",
        );

        try {
            $this->expectException(\App\Documentation\InvalidArticleException::class);
            app(ArticleRepository::class)->flush();
            app(ArticleRepository::class)->loadAll();
        } finally {
            @unlink($path);
            app(ArticleRepository::class)->flush();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createArticleWithMode(array $permissions, string $mode, bool $draft = false): object
    {
        $slug = 'test-'.strtolower(Str::random(8));
        $path = base_path('docs/user-guide/anggota/'.$slug.'.md');
        @mkdir(dirname($path), 0775, true);
        $status = $draft ? 'draft' : 'published';
        $permissionsYml = $permissions === [] ? '[]' : '['.implode(', ', array_map(static fn (string $p): string => "'{$p}'", $permissions)).']';
        file_put_contents(
            $path,
            "---\n".
            "title: Test Article\n".
            "slug: {$slug}\n".
            "summary: A test article\n".
            "category: Test\n".
            "module: test\n".
            "roles: [all]\n".
            "permissions: {$permissionsYml}\n".
            "permission_mode: {$mode}\n".
            "route_names: []\n".
            "risk_level: low\n".
            "screenshot_entries: []\n".
            "related_articles: []\n".
            "last_reviewed_commit: 20c86960\n".
            "status: {$status}\n".
            "sort_order: 999\n".
            "---\n\n# Hi\n",
        );

        $repo = app(ArticleRepository::class);
        $repo->flush();

        $article = $repo->findBySlug($slug);
        $this->assertNotNull($article, "Test article not loaded: {$slug}");

        return $article;
    }

    protected function tearDown(): void
    {
        foreach (glob(base_path('docs/user-guide/anggota/test-*.md')) ?: [] as $file) {
            @unlink($file);
        }
        app(ArticleRepository::class)->flush();
        parent::tearDown();
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function collectTargetRoles(\Illuminate\Testing\TestResponse $response): array
    {
        $articles = $this->collectArticlePayloads($response);
        $roles = [];
        foreach ($articles as $article) {
            foreach ($article['roles'] ?? [] as $role) {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @return list<string>
     */
    private function collectSlugs(\Illuminate\Testing\TestResponse $response): array
    {
        $articles = $this->collectArticlePayloads($response);

        return array_values(array_map(static fn (array $a): string => (string) $a['slug'], $articles));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectArticlePayloads(\Illuminate\Testing\TestResponse $response): array
    {
        $payload = $response->inertiaProps();
        $articles = $payload['articles'] ?? [];
        if (! is_array($articles)) {
            return [];
        }

        $out = [];
        foreach ($articles as $article) {
            if (is_array($article)) {
                $out[] = $article;
            }
        }

        return $out;
    }
}
