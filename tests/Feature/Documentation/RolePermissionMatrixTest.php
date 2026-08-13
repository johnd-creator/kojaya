<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        'Anggota',
        'Admin Koperasi',
        'Manajer Koperasi',
        'Pengurus Koperasi',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_role_permissions_json_matches_seeder_exactly(): void
    {
        $path = base_path('resources/docs/user-guide/role-permissions.json');
        $this->assertFileExists($path, 'role-permissions.json source-of-truth must exist.');

        /** @var array{roles: array<string, list<string>>} $json */
        $json = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('roles', $json, 'role-permissions.json must have a "roles" key.');

        $expectedByRole = $json['roles'];
        $this->assertSame(
            $missingRoles = array_diff(self::ROLES, array_keys($expectedByRole)),
            [],
            sprintf(
                'role-permissions.json is missing these cooperative roles: %s',
                implode(', ', $missingRoles),
            ),
        );

        foreach (self::ROLES as $spatieRole) {
            $expected = $expectedByRole[$spatieRole] ?? [];
            sort($expected);

            $role = Role::query()->where('name', $spatieRole)->first();
            $this->assertNotNull($role, "Spatie role `{$spatieRole}` not found after seeding.");

            $actual = $role->permissions->pluck('name')->all();
            sort($actual);

            $this->assertSame(
                $expected,
                $actual,
                sprintf(
                    "Permission drift for role `%s`.\n  Missing from seeder (in JSON, not granted): %s\n  Extra in seeder (granted, not in JSON): %s",
                    $spatieRole,
                    implode(', ', array_diff($expected, $actual)),
                    implode(', ', array_diff($actual, $expected)),
                ),
            );
        }
    }

    public function test_json_permissions_exist_in_database(): void
    {
        $path = base_path('resources/docs/user-guide/role-permissions.json');
        /** @var array{roles: array<string, list<string>>} $json */
        $json = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $knownPermissions = Permission::query()->pluck('name')->all();
        $knownSet = array_flip($knownPermissions);

        foreach ($json['roles'] as $spatieRole => $permissions) {
            foreach ($permissions as $permission) {
                $this->assertArrayHasKey(
                    $permission,
                    $knownSet,
                    sprintf(
                        'role-permissions.json references unknown permission `%s` (under role `%s`). '
                        .'Either add it to RolePermissionSeeder or remove it from the JSON.',
                        $permission,
                        $spatieRole,
                    ),
                );
            }
        }
    }

    public function test_seeder_does_not_grant_unknown_permissions(): void
    {
        // Scan the seeder file for any string that is the sole argument
        // to a Spatie permission grant (`givePermissionTo('x')` or
        // `firstOrCreate(['name' => 'x'])`) and assert each of them
        // exists as a Permission in the database. This catches
        // typos and re-used literals.
        $path = base_path('database/seeders/RolePermissionSeeder.php');
        $contents = File::get($path);

        $permissions = [];
        if (preg_match_all("/(?:givePermissionTo|firstOrCreate)\\(\\s*'([^']+)'/", $contents, $matches) !== false) {
            foreach ($matches[1] as $name) {
                $permissions[] = $name;
            }
        }
        $permissions = array_values(array_unique($permissions));

        $knownPermissions = Permission::query()->pluck('name')->all();
        $knownSet = array_flip($knownPermissions);

        $unknown = array_values(array_filter(
            $permissions,
            static fn (string $p): bool => ! isset($knownSet[$p]),
        ));

        $this->assertSame(
            [],
            $unknown,
            sprintf(
                'RolePermissionSeeder grants these permissions that are not declared as Permission rows: %s',
                implode(', ', $unknown),
            ),
        );
    }

    /**
     * Parse the seeder and extract every literal permission name it
     * grants. This is a coarse sanity check: it scans the file
     * for every `'foo'`-style identifier that looks like a
     * permission. The exact list is what makes this test useful —
     * if someone adds a new permission to RolePermissionSeeder
     * without updating the JSON, the first test above will fail.
     *
     * @return list<string>
     */
    private function permissionsDeclaredInSeeder(): array
    {
        $path = base_path('database/seeders/RolePermissionSeeder.php');
        $contents = File::get($path);

        $permissions = [];
        if (preg_match_all("/'(?<perm>[a-z][a-z0-9_]+)'/", $contents, $matches) !== false) {
            foreach ($matches['perm'] as $name) {
                // Heuristic: ignore Laravel built-in names like
                // "web", "sanctum", or any string that doesn't have
                // an underscore. Permission names in this codebase
                // always use snake_case.
                if (str_contains($name, '_')) {
                    $permissions[] = $name;
                }
            }
        }

        return array_values(array_unique($permissions));
    }
}
