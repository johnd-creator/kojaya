<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Documentation\ArticleRepository;
use App\Documentation\ContextualHelpRegistry;
use App\Documentation\DocumentationRoleResolver;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextualHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_sees_button_on_member_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'member.dashboard',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNotNull($entry);
        $this->assertSame('anggota', $entry['role']);
        $this->assertSame('anggota-portal-overview', $entry['slug']);
    }

    public function test_admin_sees_button_on_cooperative_loan_types_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'cooperative.loan-types.index',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNotNull($entry);
        $this->assertSame('admin_koperasi', $entry['role']);
    }

    public function test_manajer_sees_button_on_cooperative_loans_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manajer Koperasi');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'cooperative.loans.index',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNotNull($entry);
        $this->assertSame('manajer_koperasi', $entry['role']);
    }

    public function test_pengurus_sees_button_on_cooperative_loans_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Pengurus Koperasi');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'cooperative.loans.show',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNotNull($entry);
        $this->assertSame('pengurus_koperasi', $entry['role']);
    }

    public function test_button_is_hidden_without_permission(): void
    {
        // Use a test-only permission that the Admin Koperasi seeder does
        // not grant, so we can prove the registry actually enforces the
        // permission gate rather than just role matching.
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'doc_test_loan_types_perm',
            'guard_name' => 'web',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'ctxhelp').'.json';
        file_put_contents($path, json_encode([
            'version' => 1,
            'entries' => [[
                'route' => 'cooperative.loan-types.index',
                'slug' => 'admin-koperasi-loan-types',
                'role' => 'admin_koperasi',
                'permission' => 'doc_test_loan_types_perm',
                'screenshot_state' => 'default',
                'label' => 'Jenis Pinjaman',
            ]],
        ]));

        $registry = new ContextualHelpRegistry(
            $path,
            app(ArticleRepository::class),
            app(\App\Documentation\ArticleAuthorizer::class),
        );

        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        // intentionally NOT granting doc_test_loan_types_perm

        $entry = $registry->resolveForRequest(
            'cooperative.loan-types.index',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNull($entry, 'Button must be hidden when the user lacks the required permission.');

        // Sanity check: granting the permission flips the result.
        $user->givePermissionTo('doc_test_loan_types_perm');
        $entry = $registry->resolveForRequest(
            'cooperative.loan-types.index',
            $user,
            app(DocumentationRoleResolver::class),
        );
        $this->assertNotNull($entry);
    }

    public function test_button_is_hidden_for_wrong_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'cooperative.loan-types.index',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNull($entry, 'Button must be hidden when the user is on the wrong role bucket.');
    }

    public function test_button_returns_null_when_no_mapping(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'this.route.has.no.mapping',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNull($entry);
    }

    public function test_button_returns_null_when_referenced_article_does_not_exist(): void
    {
        // Build a registry that points at a non-existent slug. The
        // resolve path must reject the entry rather than crash.
        $path = tempnam(sys_get_temp_dir(), 'ctxhelp').'.json';
        file_put_contents($path, json_encode([
            'version' => 1,
            'entries' => [[
                'route' => 'member.dashboard',
                'slug' => 'this-article-does-not-exist',
                'role' => 'anggota',
                'screenshot_state' => 'default',
                'label' => 'Broken',
            ]],
        ]));

        $registry = new ContextualHelpRegistry(
            $path,
            app(ArticleRepository::class),
            app(\App\Documentation\ArticleAuthorizer::class),
        );

        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $entry = $registry->resolveForRequest(
            'member.dashboard',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNull($entry);
    }

    public function test_button_target_article_is_actually_openable_by_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $entry = app(ContextualHelpRegistry::class)->resolveForRequest(
            'member.dashboard',
            $user,
            app(DocumentationRoleResolver::class),
        );

        $this->assertNotNull($entry);

        $response = $this->actingAs($user)
            ->get('/documentation/'.$entry['slug']);

        $response->assertOk();
    }

    public function test_duplicates_in_registry_are_reported(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ctxhelp').'.json';
        file_put_contents($path, json_encode([
            'version' => 1,
            'entries' => [
                [
                    'route' => 'member.dashboard',
                    'slug' => 'anggota-portal-overview',
                    'role' => 'anggota',
                    'screenshot_state' => 'a',
                    'label' => 'First',
                ],
                [
                    'route' => 'member.dashboard',
                    'slug' => 'anggota-portal-overview',
                    'role' => 'anggota',
                    'screenshot_state' => 'b',
                    'label' => 'Duplicate',
                ],
            ],
        ]));

        $registry = new ContextualHelpRegistry(
            $path,
            app(ArticleRepository::class),
            app(\App\Documentation\ArticleAuthorizer::class),
        );
        $registry->all();

        $this->assertNotEmpty($registry->duplicates());
    }
}
