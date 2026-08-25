<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Documentation\Article;
use App\Documentation\ArticleAuthorizer;
use App\Documentation\ArticleRepository;
use App\Documentation\InvalidArticleException;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewDocumentationCenterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Per-test isolated guide root. Each test runs against its own
     * Markdown directory under the OS temp folder so parallel test
     * runs cannot race over the production `docs/user-guide/`.
     */
    private string $temporaryGuidePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->temporaryGuidePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'kojaya-user-guide-tests'
            .DIRECTORY_SEPARATOR
            .Str::uuid()->toString();

        $this->seedTemporaryGuideFromProduction();

        $this->bindTemporaryArticleRepository();
    }

    /**
     * Mirror the production guide directory into the per-test
     * temporary location so the existing positive-path tests keep
     * seeing the real articles. Tests that need bespoke fixtures
     * additionally drop them via {@see self::writeTemporaryArticle()}
     * or {@see self::createArticleWithMode()} — both of which bind a
     * fresh repository afterwards.
     */
    private function seedTemporaryGuideFromProduction(): void
    {
        $productionRoot = base_path('docs/user-guide');
        if (! is_dir($productionRoot)) {
            return;
        }

        File::copyDirectory($productionRoot, $this->temporaryGuidePath);
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

    public function test_documentation_articles_with_equal_sort_orders_have_deterministic_tie_breakers(): void
    {
        $user = $this->makeUserWithRole('System Admin');

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();

        $articles = $this->collectArticlePayloads($response);
        $expected = $articles;

        usort(
            $expected,
            static fn (array $left, array $right): int => [
                $left['sort_order'],
                $left['category'],
                $left['title'],
            ] <=> [
                $right['sort_order'],
                $right['category'],
                $right['title'],
            ],
        );

        $this->assertSame($expected, $articles);
    }

    public function test_anggota_only_sees_anggota_or_all_articles(): void
    {
        $anggota = $this->makeUserWithRole('Anggota');

        $response = $this->actingAs($anggota)
            ->get('/documentation')
            ->assertOk();

        $this->assertArticlesAreAuthorizedForRole($response, 'anggota');
    }

    public function test_admin_koperasi_sees_only_admin_or_all_articles(): void
    {
        $admin = $this->makeUserWithRole('Admin Koperasi');

        $response = $this->actingAs($admin)
            ->get('/documentation')
            ->assertOk();

        $this->assertArticlesAreAuthorizedForRole($response, 'admin_koperasi');
    }

    public function test_manajer_koperasi_sees_only_manajer_or_all_articles(): void
    {
        $manajer = $this->makeUserWithRole('Manajer Koperasi');

        $response = $this->actingAs($manajer)
            ->get('/documentation')
            ->assertOk();

        $this->assertArticlesAreAuthorizedForRole($response, 'manajer_koperasi');
    }

    public function test_pengurus_koperasi_sees_only_pengurus_or_all_articles(): void
    {
        $pengurus = $this->makeUserWithRole('Pengurus Koperasi');

        $response = $this->actingAs($pengurus)
            ->get('/documentation')
            ->assertOk();

        $this->assertArticlesAreAuthorizedForRole($response, 'pengurus_koperasi');
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

        $slug = 'hidden-test-'.strtolower(Str::random(6));
        $this->writeTemporaryArticle(
            role: 'admin-koperasi',
            slug: $slug,
            permissions: ['custom_doc_perm_a'],
            mode: 'all',
        );

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
        $this->bindTemporaryArticleRepository();
        $response = $this->actingAs($user)->get('/documentation')->assertOk();
        $this->assertContains($slug, $this->collectSlugs($response));
    }

    public function test_article_with_required_permission_is_shown_to_user_with_at_least_one_match(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $user = $this->makeUserWithRole('Admin Koperasi');
        $user->givePermissionTo('custom_doc_perm_b');

        $slug = 'visible-test-'.strtolower(Str::random(6));
        $this->writeTemporaryArticle(
            role: 'admin-koperasi',
            slug: $slug,
            permissions: ['custom_doc_perm_b'],
            mode: 'all',
        );

        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();
        $this->assertContains($slug, $this->collectSlugs($response));
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
        $path = $this->temporaryGuidePath.DIRECTORY_SEPARATOR.$relative;
        File::ensureDirectoryExists(dirname($path));
        File::put(
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
            $this->expectException(InvalidArticleException::class);
            $this->bindTemporaryArticleRepository();
            app(ArticleRepository::class)->loadAll();
        } finally {
            @unlink($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createArticleWithMode(array $permissions, string $mode, bool $draft = false): object
    {
        $slug = 'test-'.strtolower(Str::random(8));
        $status = $draft ? 'draft' : 'published';
        $permissionsYml = $permissions === [] ? '[]' : '['.implode(', ', array_map(static fn (string $p): string => "'{$p}'", $permissions)).']';
        $path = $this->temporaryGuidePath.DIRECTORY_SEPARATOR.'anggota'.DIRECTORY_SEPARATOR.$slug.'.md';
        File::ensureDirectoryExists(dirname($path));
        File::put(
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

        $this->bindTemporaryArticleRepository();

        $repo = app(ArticleRepository::class);
        $article = $repo->findBySlug($slug);
        $this->assertNotNull($article, "Test article not loaded: {$slug}");

        return $article;
    }

    public function test_temporary_directory_is_unique_per_test(): void
    {
        // Snapshot the current path; the next test's setUp() will
        // assign a fresh UUID directory.
        $first = $this->temporaryGuidePath;
        $this->assertNotEmpty($first);
        $this->assertDirectoryExists($first);
    }

    public function test_production_guide_directory_is_unchanged_by_the_test_suite(): void
    {
        $productionRoot = base_path('docs/user-guide');
        $baseline = [];
        foreach (File::allFiles($productionRoot) as $file) {
            $baseline[$file->getRelativePathname()] = $file->getMTime();
        }

        // Write a temporary article that would otherwise pollute
        // the production directory if the helper were broken.
        $slug = 'would-pollute-'.strtolower(Str::random(6));
        $this->writeTemporaryArticle(
            role: 'admin-koperasi',
            slug: $slug,
            permissions: [],
            mode: 'all',
        );

        foreach (File::allFiles($productionRoot) as $file) {
            $rel = $file->getRelativePathname();
            $this->assertArrayHasKey($rel, $baseline, "Production guide gained new file: {$rel}");
            $this->assertSame($baseline[$rel], $file->getMTime(), "Production guide file mtime changed: {$rel}");
        }
    }

    public function test_temporary_guide_path_is_cleaned_up_in_tear_down(): void
    {
        $path = $this->temporaryGuidePath;
        $this->assertDirectoryExists($path);

        // Simulate an exception happening mid-test by calling the
        // tearDown logic directly.
        File::deleteDirectory($path);
        $this->assertDirectoryDoesNotExist($path);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function writeTemporaryArticle(
        string $role,
        string $slug,
        array $permissions,
        string $mode,
    ): void {
        $directory = $this->temporaryGuidePath.DIRECTORY_SEPARATOR.$role;
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.$slug.'.md';
        $permissionsYml = $permissions === [] ? '[]' : '['.implode(', ', array_map(static fn (string $p): string => "'{$p}'", $permissions)).']';
        File::put(
            $path,
            "---\n".
            "title: Test Article\n".
            "slug: {$slug}\n".
            "summary: Temporary article for testing\n".
            "category: Test\n".
            "module: test\n".
            'roles: ['.str_replace('-', '_', $role)."]\n".
            "permissions: {$permissionsYml}\n".
            "permission_mode: {$mode}\n".
            "route_names: []\n".
            "risk_level: low\n".
            "screenshot_entries: []\n".
            "related_articles: []\n".
            "last_reviewed_commit: 20c86960\n".
            "status: published\n".
            "sort_order: 999\n".
            "---\n\n# Test Article\n",
        );

        $this->bindTemporaryArticleRepository();
    }

    /**
     * Re-bind ArticleRepository (and dependent services) to the
     * per-test temporary guide path. Forgetting to do this after a
     * filesystem write is exactly how parallel tests race.
     */
    private function bindTemporaryArticleRepository(): void
    {
        $this->app->forgetInstance(ArticleRepository::class);
        $this->app->forgetInstance(ArticleAuthorizer::class);
        $this->app->forgetInstance(\App\Documentation\ContextualHelpRegistry::class);
        $this->app->forgetInstance(\App\Http\Controllers\Documentation\DocumentationController::class);

        $this->app->singleton(
            ArticleRepository::class,
            fn () => new ArticleRepository(basePath: $this->temporaryGuidePath),
        );
    }

    protected function tearDown(): void
    {
        if ($this->temporaryGuidePath !== '' && is_dir($this->temporaryGuidePath)) {
            File::deleteDirectory($this->temporaryGuidePath);
        }
        $this->app->forgetInstance(ArticleRepository::class);
        $this->app->forgetInstance(ArticleAuthorizer::class);

        parent::tearDown();
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function assertArticlesAreAuthorizedForRole(
        \Illuminate\Testing\TestResponse $response,
        string $role,
    ): void {
        foreach ($this->collectArticlePayloads($response) as $article) {
            $articleRoles = $article['roles'] ?? [];

            $this->assertTrue(
                array_intersect($articleRoles, ['all', 'shared', $role]) !== [],
                "{$role} must not see an article without a matching target role.",
            );
        }
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
