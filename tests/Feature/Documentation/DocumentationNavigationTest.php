<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Asserts that the Pusat Panduan link is reachable from every
 * cooperative role's navigation array. The link is configured in
 * `resources/js/components/AppSidebar.vue` (member, admin, all)
 * and surfaced as `footerNavItems`. We test the underlying
 * repository behaviour (the route is reachable and the menu
 * contains the slug) so the test is stable across Vue refactors.
 */
class DocumentationNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_documentation_route_is_reachable_for_role(string $role): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        // The landing page should respond with 200 for any authenticated
        // user. The page itself renders the navigation with the link.
        $response = $this->actingAs($user)
            ->get('/documentation')
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Documentation/Index')
            ->has('sections'),
        );
    }

    /**
     * @return list<array{0: string}>
     */
    public static function roleProvider(): array
    {
        return [
            ['Anggota'],
            ['Admin Koperasi'],
            ['Manajer Koperasi'],
            ['Pengurus Koperasi'],
        ];
    }

    public function test_documentation_link_is_defined_in_app_sidebar(): void
    {
        $source = file_get_contents(base_path('resources/js/components/AppSidebar.vue'));
        $this->assertNotFalse($source);

        // Footer slot
        $this->assertStringContainsString(
            'footerNavItems',
            $source,
            'AppSidebar.vue must declare `footerNavItems` for centralised footer navigation.',
        );

        // Member nav (active financial features branch)
        $this->assertMatchesRegularExpression(
            '/memberNavItems[\s\S]+Pusat Panduan[\s\S]+\/documentation/',
            $source,
            'Active-member sidebar must include Pusat Panduan.',
        );

        // Member nav (inactive branch)
        $this->assertMatchesRegularExpression(
            '/can_access_financial_features[\s\S]+Pusat Panduan[\s\S]+\/documentation/',
            $source,
            'Inactive-member sidebar must include Pusat Panduan.',
        );

        // Admin nav
        $this->assertMatchesRegularExpression(
            '/adminNavItems[\s\S]+Pusat Panduan[\s\S]+\/documentation/',
            $source,
            'Admin Koperasi sidebar must include Pusat Panduan.',
        );

        // Manajer/Pengurus all-items nav
        $this->assertMatchesRegularExpression(
            '/allNavItems[\s\S]+Pusat Panduan[\s\S]+\/documentation/',
            $source,
            'Generic (Manajer / Pengurus) sidebar must include Pusat Panduan.',
        );
    }
}
