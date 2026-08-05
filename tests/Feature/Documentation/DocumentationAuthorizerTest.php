<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Documentation\Article;
use App\Documentation\ArticleAuthorizer;
use App\Documentation\ArticleFrontmatter;
use App\Documentation\DocumentationRoleResolver;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentationAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_only_sees_anggota_and_shared_articles(): void
    {
        $user = $this->makeUserWithSpatieRole('Anggota');

        $response = $this->actingAs($user)->get('/documentation')->assertOk();
        $page = $response->inertiaPage();
        $slugs = $this->collectArticleSlugs($page);

        $this->assertContains('anggota-loan-flow', $slugs);
        $this->assertNotContains('admin-koperasi-loan-types', $slugs);
        $this->assertNotContains('manajer-loan-review', $slugs);
        $this->assertNotContains('pengurus-loan-approval', $slugs);
    }

    public function test_multi_role_anggota_admin_routes_to_admin_articles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $user->assignRole('Admin Koperasi');

        $this->actingAs($user)
            ->get('/documentation/admin-koperasi-loan-types')
            ->assertOk();

        // Primary role is Admin Koperasi (higher priority); anggota
        // articles are NOT visible.
        $this->actingAs($user)
            ->get('/documentation/anggota-loan-flow')
            ->assertForbidden();
    }

    public function test_multi_role_admin_manajer_routes_to_manajer_articles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $user->assignRole('Manajer Koperasi');
        $user->givePermissionTo('review_cooperative_loan');
        $user->givePermissionTo('view_cooperative_report');

        $this->actingAs($user)
            ->get('/documentation/manajer-loan-review')
            ->assertOk();

        // The Admin Koperasi article should be 403 because the primary
        // role is Manajer (higher priority per PrimaryRoleResolver).
        $this->actingAs($user)
            ->get('/documentation/admin-koperasi-loan-types')
            ->assertForbidden();
    }

    public function test_multi_role_manajer_pengurus_routes_to_pengurus_articles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manajer Koperasi');
        $user->assignRole('Pengurus Koperasi');
        $user->givePermissionTo('approve_cooperative_loan');
        $user->givePermissionTo('manage_cooperative_shu');

        $this->actingAs($user)
            ->get('/documentation/pengurus-loan-approval')
            ->assertOk();

        $this->actingAs($user)
            ->get('/documentation/manajer-loan-review')
            ->assertForbidden();
    }

    public function test_system_admin_sees_every_published_article(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        foreach (
            [
                'anggota-loan-flow',
                'admin-koperasi-loan-types',
                'manajer-loan-review',
                'pengurus-loan-approval',
            ] as $slug
        ) {
            $this->actingAs($user)
                ->get('/documentation/'.$slug)
                ->assertOk();
        }
    }

    public function test_generic_role_only_sees_shared_articles(): void
    {
        // A user with a Spatie role that does not map to any cooperative
        // bucket (e.g. "Project Manager" from the platform side) should
        // only be able to read shared articles.
        $user = User::factory()->create();
        $user->assignRole('Project Manager');

        $resolver = app(DocumentationRoleResolver::class);
        $this->assertSame(DocumentationRoleResolver::ROLE_GENERIC, $resolver->resolve($user));

        // Shared article should be visible (it has `roles: [shared]`).
        $shared = $this->makeArticle(['roles' => ['shared']]);
        $this->assertTrue(app(ArticleAuthorizer::class)->canView($user, $shared));

        // Role-specific article is forbidden.
        $anggota = $this->makeArticle(['roles' => ['anggota']]);
        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $anggota));
    }

    public function test_direct_url_to_other_role_article_returns_403(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $this->actingAs($anggota)
            ->get('/documentation/admin-koperasi-loan-types')
            ->assertForbidden();

        $this->actingAs($anggota)
            ->get('/documentation/manajer-loan-review')
            ->assertForbidden();

        $this->actingAs($anggota)
            ->get('/documentation/pengurus-loan-approval')
            ->assertForbidden();
    }

    public function test_authorizer_rejects_invalid_role_in_frontmatter(): void
    {
        $this->expectException(\App\Documentation\InvalidArticleException::class);
        $this->makeArticle(['roles' => ['unknown_role'], 'permissions' => []]);
    }

    public function test_authorizer_handles_permission_mode_all(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_a', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $article = $this->makeArticle([
            'roles' => ['admin_koperasi'],
            'permissions' => ['custom_doc_perm_a', 'custom_doc_perm_b'],
            'permission_mode' => 'all',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $article));

        $user->givePermissionTo('custom_doc_perm_a');
        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $article));

        $user->givePermissionTo('custom_doc_perm_b');
        $this->assertTrue(app(ArticleAuthorizer::class)->canView($user, $article));
    }

    public function test_authorizer_handles_permission_mode_any(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_a', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'custom_doc_perm_b', 'guard_name' => 'web']);

        $article = $this->makeArticle([
            'roles' => ['admin_koperasi'],
            'permissions' => ['custom_doc_perm_a', 'custom_doc_perm_b'],
            'permission_mode' => 'any',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $article));

        $user->givePermissionTo('custom_doc_perm_a');
        $this->assertTrue(app(ArticleAuthorizer::class)->canView($user, $article));
    }

    public function test_unpublished_article_is_hidden_from_everyone_including_system_admin(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $article));
    }

    public function test_invalid_slug_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get('/documentation/this-slug-does-not-exist')
            ->assertNotFound();
    }

    public function test_documentation_role_resolver_recognises_anggota_explicitly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $resolver = app(DocumentationRoleResolver::class);
        $this->assertSame(DocumentationRoleResolver::ROLE_ANGGOTA, $resolver->resolve($user));
    }

    public function test_documentation_role_resolver_does_not_use_substring_matching(): void
    {
        // A user with role "Site Manager" must NOT be routed to the
        // manajer_koperasi bucket just because "manajer" appears in the
        // name. This is a regression test for the substring bug.
        $user = User::factory()->create();
        $user->assignRole('Site Manager');

        $resolver = app(DocumentationRoleResolver::class);
        $this->assertSame(DocumentationRoleResolver::ROLE_GENERIC, $resolver->resolve($user));
    }

    public function test_documentation_drift_audit_command_is_replaced_by_docs_validate(): void
    {
        // The old `docs:audit-drift` command was removed in the
        // refactor; the equivalent coverage now lives in the
        // docs:validate Node script and the per-article tests above.
        $this->expectException(\Symfony\Component\Console\Exception\CommandNotFoundException::class);
        Artisan::call('docs:audit-drift', ['--source' => 'database']);
    }

    /**
     * @param  list<string>  $spatieRoleNames
     */
    private function makeUserWithSpatieRole(string ...$spatieRoleNames): User
    {
        $user = User::factory()->create();
        foreach ($spatieRoleNames as $name) {
            $user->assignRole($name);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return list<string>
     */
    private function collectArticleSlugs(array $page): array
    {
        $articles = $page['props']['articles'] ?? [];
        $slugs = [];
        foreach ($articles as $article) {
            if (is_array($article) && isset($article['slug']) && is_string($article['slug'])) {
                $slugs[] = $article['slug'];
            }
        }

        return $slugs;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeArticle(array $overrides): Article
    {
        $slug = 'auth-'.strtolower(Str::random(8));
        $payload = array_merge([
            'title' => 'Test Article',
            'slug' => $slug,
            'summary' => 'A test article for authorization',
            'category' => 'Test',
            'module' => 'test',
            'roles' => ['all'],
            'permissions' => [],
            'permission_mode' => 'all',
            'route_names' => [],
            'risk_level' => 'low',
            'screenshot_entries' => [],
            'related_articles' => [],
            'last_reviewed_commit' => '20c86960',
            'status' => 'published',
            'sort_order' => 999,
        ], $overrides);

        $frontmatter = ArticleFrontmatter::fromArray($payload, $slug.'.md');

        return new Article(
            frontmatter: $frontmatter,
            body: '# Test',
            relativePath: 'test/'.$slug.'.md',
        );
    }
}
