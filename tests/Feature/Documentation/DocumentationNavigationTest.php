<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Asserts that the Pusat Panduan link is reachable from every
 * cooperative role and is declared once in the shared sidebar footer.
 * The route remains backend-authorized; this test only protects the
 * presentation contract that keeps help separate from work menus.
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

        // The footer is the single shared location for help navigation.
        $this->assertStringContainsString(
            'footerNavItems',
            $source,
        );
        $this->assertSame(
            1,
            substr_count($source, 'title: "Pusat Panduan"'),
            'AppSidebar.vue must declare Pusat Panduan only once.',
        );
        $this->assertMatchesRegularExpression(
            '/footerNavItems[\s\S]+Pusat Panduan[\s\S]+\/documentation/',
            $source,
            'Pusat Panduan must remain in footerNavItems.',
        );

        $footerSource = file_get_contents(base_path('resources/js/components/NavFooter.vue'));
        $this->assertNotFalse($footerSource);
        $this->assertStringContainsString('<Link', $footerSource);
        $this->assertStringContainsString(
            'v-if="!isExternal(item.href)"',
            $footerSource,
        );
    }
}
