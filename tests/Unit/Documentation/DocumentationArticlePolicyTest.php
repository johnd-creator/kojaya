<?php

namespace Tests\Unit\Documentation;

use App\Models\DocumentationArticle;
use App\Models\User;
use App\Policies\DocumentationPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_view_any_returns_true_for_any_authenticated_user(): void
    {
        $policy = new DocumentationPolicy;
        $anggota = User::factory()->create()->assignRole('Anggota');

        $this->assertTrue($policy->viewAny($anggota));
    }

    public function test_view_returns_false_for_unpublished_article(): void
    {
        $policy = new DocumentationPolicy;
        $user = User::factory()->create()->assignRole('Anggota');
        $article = DocumentationArticle::factory()
            ->forRole('anggota')
            ->unpublished()
            ->create();

        $this->assertFalse($policy->view($user, $article));
    }

    public function test_view_returns_false_when_user_does_not_have_target_role(): void
    {
        $policy = new DocumentationPolicy;
        $anggota = User::factory()->create()->assignRole('Anggota');
        $article = DocumentationArticle::factory()
            ->forRole('pengurus_koperasi')
            ->create();

        $this->assertFalse($policy->view($anggota, $article));
    }

    public function test_view_returns_true_when_article_has_no_required_permissions(): void
    {
        $policy = new DocumentationPolicy;
        $manajer = User::factory()->create()->assignRole('Manajer Koperasi');
        $article = DocumentationArticle::factory()
            ->forRole('manajer_koperasi')
            ->create([
                'required_permissions' => null,
            ]);

        $this->assertTrue($policy->view($manajer, $article));
    }

    public function test_view_returns_true_when_user_has_at_least_one_required_permission(): void
    {
        $policy = new DocumentationPolicy;
        $manajer = User::factory()->create()->assignRole('Manajer Koperasi');

        $article = DocumentationArticle::factory()
            ->forRole('manajer_koperasi')
            ->withPermission('review_cooperative_loan')
            ->create();

        $this->assertTrue($policy->view($manajer, $article));
    }

    public function test_view_returns_false_when_user_lacks_all_required_permissions(): void
    {
        $policy = new DocumentationPolicy;
        $anggota = User::factory()->create()->assignRole('Anggota');

        $article = DocumentationArticle::factory()
            ->forRole('anggota')
            ->withPermission('review_cooperative_loan')
            ->create();

        $this->assertFalse($policy->view($anggota, $article));
    }

    public function test_create_requires_manage_roles_permission(): void
    {
        $policy = new DocumentationPolicy;
        $anggota = User::factory()->create()->assignRole('Anggota');
        $admin = User::factory()->create()->assignRole('System Admin');

        $this->assertFalse($policy->create($anggota));
        $this->assertTrue($policy->create($admin));
    }

    public function test_update_and_delete_inherit_create(): void
    {
        $policy = new DocumentationPolicy;
        $anggota = User::factory()->create()->assignRole('Anggota');
        $admin = User::factory()->create()->assignRole('System Admin');
        $article = DocumentationArticle::factory()->create();

        $this->assertFalse($policy->update($anggota, $article));
        $this->assertTrue($policy->update($admin, $article));
        $this->assertFalse($policy->delete($anggota, $article));
        $this->assertTrue($policy->delete($admin, $article));
    }

    public function test_target_role_mapping_handles_spatie_role_names(): void
    {
        $this->assertSame(
            ['all', 'admin_koperasi'],
            DocumentationArticle::targetRolesForUser(['Admin Koperasi']),
        );
        $this->assertSame(
            ['all', 'manajer_koperasi'],
            DocumentationArticle::targetRolesForUser(['Manajer Koperasi']),
        );
        $this->assertSame(
            ['all', 'pengurus_koperasi'],
            DocumentationArticle::targetRolesForUser(['Pengurus Koperasi']),
        );
        $this->assertSame(
            ['all', 'anggota'],
            DocumentationArticle::targetRolesForUser(['Anggota']),
        );
        $this->assertSame(
            ['all', 'admin_koperasi', 'manajer_koperasi', 'pengurus_koperasi'],
            DocumentationArticle::targetRolesForUser([
                'Admin Koperasi',
                'Manajer Koperasi',
                'Pengurus Koperasi',
            ]),
        );
        // Unknown roles still get the 'all' bucket
        $this->assertSame(
            ['all'],
            DocumentationArticle::targetRolesForUser(['Site Manager']),
        );
    }
}
