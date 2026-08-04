<?php

namespace Tests\Feature\Documentation;

use App\Models\DocumentationArticle;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_anggota_only_sees_anggota_articles(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $anggota = $this->makeUserWithRole('Anggota');

        $response = $this->actingAs($anggota)
            ->get('/documentation')
            ->assertOk();

        $roles = $this->collectTargetRoles($response);

        $this->assertNotEmpty($roles, 'Anggota should see at least one article.');
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
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $admin = $this->makeUserWithRole('Admin Koperasi');

        $response = $this->actingAs($admin)
            ->get('/documentation')
            ->assertOk();

        $roles = $this->collectTargetRoles($response);
        foreach ($roles as $role) {
            $this->assertContains(
                $role,
                ['all', 'admin_koperasi'],
                "Admin Koperasi must not see an article with target_role={$role}",
            );
        }
    }

    public function test_manajer_koperasi_sees_only_manajer_or_all_articles(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $manajer = $this->makeUserWithRole('Manajer Koperasi');

        $response = $this->actingAs($manajer)
            ->get('/documentation')
            ->assertOk();

        $roles = $this->collectTargetRoles($response);
        foreach ($roles as $role) {
            $this->assertContains(
                $role,
                ['all', 'manajer_koperasi'],
                "Manajer Koperasi must not see an article with target_role={$role}",
            );
        }
    }

    public function test_pengurus_koperasi_sees_only_pengurus_or_all_articles(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $pengurus = $this->makeUserWithRole('Pengurus Koperasi');

        $response = $this->actingAs($pengurus)
            ->get('/documentation')
            ->assertOk();

        $roles = $this->collectTargetRoles($response);
        foreach ($roles as $role) {
            $this->assertContains(
                $role,
                ['all', 'pengurus_koperasi'],
                "Pengurus Koperasi must not see an article with target_role={$role}",
            );
        }
    }

    public function test_show_route_returns_403_when_user_lacks_target_role(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $anggota = $this->makeUserWithRole('Anggota');

        $article = DocumentationArticle::where('target_role', 'pengurus_koperasi')->firstOrFail();

        $this->actingAs($anggota)
            ->get('/documentation/'.$article->slug)
            ->assertForbidden();
    }

    public function test_show_route_returns_200_for_user_with_required_role(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $pengurus = $this->makeUserWithRole('Pengurus Koperasi');

        $article = DocumentationArticle::where('target_role', 'pengurus_koperasi')->firstOrFail();

        $this->actingAs($pengurus)
            ->get('/documentation/'.$article->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Documentation/Show')
                ->where('article.slug', $article->slug),
            );
    }

    public function test_unpublished_articles_are_excluded_from_index(): void
    {
        $anggota = $this->makeUserWithRole('Anggota');

        $unpublished = DocumentationArticle::factory()->forRole('anggota')->unpublished()->create();
        $published = DocumentationArticle::factory()->forRole('anggota')->create();

        $response = $this->actingAs($anggota)
            ->get('/documentation')
            ->assertOk();

        $ids = $this->collectArticleIds($response);
        $this->assertContains($published->id, $ids);
        $this->assertNotContains($unpublished->id, $ids);
    }

    public function test_article_with_required_permission_is_hidden_from_user_without_it(): void
    {
        $anggota = $this->makeUserWithRole('Anggota');

        $article = DocumentationArticle::factory()
            ->forRole('anggota')
            ->withPermission('review_cooperative_loan')
            ->create();

        $response = $this->actingAs($anggota)
            ->get('/documentation')
            ->assertOk();

        $ids = $this->collectArticleIds($response);
        $this->assertNotContains($article->id, $ids);
    }

    public function test_article_with_required_permission_is_shown_to_user_with_at_least_one_match(): void
    {
        $manajer = $this->makeUserWithRole('Manajer Koperasi');

        $article = DocumentationArticle::factory()
            ->forRole('manajer_koperasi')
            ->withPermission('review_cooperative_loan')
            ->create();

        $response = $this->actingAs($manajer)
            ->get('/documentation')
            ->assertOk();

        $ids = $this->collectArticleIds($response);
        $this->assertContains($article->id, $ids);
    }

    public function test_documentation_drift_audit_command_passes_against_seeded_articles(): void
    {
        $this->seed(\Database\Seeders\DocumentationArticleSeeder::class);

        $exit = \Illuminate\Support\Facades\Artisan::call('docs:audit-drift', ['--source' => 'database']);

        $this->assertSame(0, $exit, \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_documentation_drift_audit_command_passes_against_markdown_files(): void
    {
        $exit = \Illuminate\Support\Facades\Artisan::call('docs:audit-drift', ['--source' => 'markdown']);

        $this->assertSame(0, $exit, \Illuminate\Support\Facades\Artisan::output());
    }

    private function makeUserWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    /**
     * @return list<int>
     */
    private function collectArticleIds(\Illuminate\Testing\TestResponse $response): array
    {
        $sections = $this->collectSections($response);

        return collect($sections)->flatMap(
            fn ($section) => collect($section['articles'])->pluck('id'),
        )->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return list<string>
     */
    private function collectTargetRoles(\Illuminate\Testing\TestResponse $response): array
    {
        $sections = $this->collectSections($response);

        return collect($sections)->flatMap(
            fn ($section) => collect($section['articles'])->pluck('target_role'),
        )->map(fn ($role) => (string) $role)->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectSections(\Illuminate\Testing\TestResponse $response): array
    {
        $page = $response->inertiaPage();
        $props = $page['props'] ?? [];

        return $props['sections'] ?? [];
    }
}
