<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Documentation\Article;
use App\Documentation\ArticleAuthorizer;
use App\Documentation\ArticleFrontmatter;
use App\Documentation\ArticleRepository;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_multi_role_anggota_and_admin_can_view_articles_for_either_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $user->assignRole('Admin Koperasi');

        $this->actingAs($user)
            ->get('/documentation/admin-koperasi-operational-dashboard')
            ->assertOk();

        $this->actingAs($user)
            ->get('/documentation/anggota-portal-overview')
            ->assertOk();
    }

    public function test_multi_role_admin_and_manajer_sees_both_articles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $user->assignRole('Manajer Koperasi');
        $user->givePermissionTo('review_cooperative_loan');
        $user->givePermissionTo('view_cooperative_report');

        $this->actingAs($user)
            ->get('/documentation/manajer-loan-review')
            ->assertOk();

        $this->actingAs($user)
            ->get('/documentation/admin-koperasi-operational-dashboard')
            ->assertOk();
    }

    public function test_system_admin_can_view_articles_for_every_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        foreach (
            [
                'anggota-portal-overview',
                'admin-koperasi-operational-dashboard',
                'manajer-loan-review',
                'pengurus-loan-approval',
            ] as $slug
        ) {
            $this->actingAs($user)
                ->get('/documentation/'.$slug)
                ->assertOk();
        }
    }

    public function test_direct_url_to_other_role_article_returns_403(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $this->actingAs($anggota)
            ->get('/documentation/admin-koperasi-operational-dashboard')
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
        // `fromArray` itself rejects unknown roles; the repository would
        // surface that as InvalidArticleException. We assert the
        // exception class so the contract is explicit.
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

    public function test_unpublished_article_is_rejected_by_authorizer(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->assertFalse(app(ArticleAuthorizer::class)->canView($user, $article));
    }

    public function test_repository_returns_filtered_visible_collection(): void
    {
        $repo = app(ArticleRepository::class);
        $repo->flush();
        $user = User::factory()->create();
        $user->assignRole('Manajer Koperasi');
        $user->givePermissionTo('review_cooperative_loan');
        $user->givePermissionTo('view_cooperative_report');

        $visible = $repo->visibleTo(['Manajer Koperasi'], ['review_cooperative_loan', 'view_cooperative_report']);
        $slugs = array_map(static fn (Article $a): string => $a->slug(), $visible->all());

        $this->assertContains('manajer-loan-review', $slugs);
        $this->assertNotContains('admin-koperasi-loan-types', $slugs);
        $this->assertNotContains('anggota-loan-flow', $slugs);
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
        $article = new Article(
            frontmatter: $frontmatter,
            body: '# Test',
            relativePath: 'test/'.$slug.'.md',
        );

        return $article;
    }
}
