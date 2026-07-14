<?php

namespace Tests\Feature\Cooperative;

use App\Enums\PermissionEnum;
use App\Exports\AnggotaExport;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationVisibility;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MemberExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_role_is_forbidden_from_exporting_members(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_admin_without_export_permission_is_forbidden(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        // Kasir Koperasi has view_cooperative_member but NOT export_cooperative_member
        $user->assignRole('Kasir Koperasi');

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_admin_koperasi_with_export_permission_can_export(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $this->actingAs($admin)
            ->get(route('cooperative.members.export'))
            ->assertOk();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('cooperative.members.export'))
            ->assertRedirect('/login');
    }

    public function test_export_is_scoped_to_organization_for_non_global_admin(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'no_anggota' => 'AAA-001',
            'member_no' => 'AAA-001',
            'name' => 'Member Org A',
        ]);

        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'no_anggota' => 'BBB-002',
            'member_no' => 'BBB-002',
            'name' => 'Member Org B',
        ]);

        $scoped = new AnggotaExport([], OrganizationVisibility::organization((string) $orgA->id));
        $scopedNames = $scoped->query()->pluck('name')->all();

        $this->assertContains('Member Org A', $scopedNames);
        $this->assertNotContains('Member Org B', $scopedNames);
    }

    public function test_export_masks_pii_fields(): void
    {
        $organization = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234567890',
        ]);

        $scoped = new AnggotaExport([], OrganizationVisibility::organization((string) $organization->id));
        $row = $scoped->query()->first();
        $mapped = $scoped->map($row);

        // NPWP at position 5, No Rekening at position 11
        $this->assertNotEquals('12.345.678.9-012.000', $mapped[5]);
        $this->assertNotEquals('1234567890', $mapped[11]);
        $this->assertStringEndsWith('7890', $mapped[11]);
    }

    public function test_actual_export_content_is_scoped_to_the_requested_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'SENTINEL-ORG-A',
            'nama_anggota' => 'SENTINEL-ORG-A',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'SENTINEL-ORG-B',
            'nama_anggota' => 'SENTINEL-ORG-B',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'member-export-');
        file_put_contents($path, Excel::raw(new AnggotaExport([], OrganizationVisibility::organization((string) $orgA->id)), ExcelFormat::XLSX));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        $content = implode(' ', array_map(
            static fn (array $row): string => implode(' ', array_map(static fn (mixed $value): string => (string) $value, $row)),
            $rows,
        ));

        $this->assertStringContainsString('SENTINEL-ORG-A', $content);
        $this->assertStringNotContainsString('SENTINEL-ORG-B', $content);
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }

    public function test_non_global_user_without_organization_gets_403_on_export(): void
    {
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole('Admin Koperasi');

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_audit_log_stores_explicit_export_scope(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $this->actingAs($admin)
            ->get(route('cooperative.members.export'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.pii.exported',
            'new_values->scope' => 'organization',
        ]);
    }

    public function test_pengurus_global_export_includes_multiple_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $orgA->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'SENTINEL-GLOBAL-A',
            'nama_anggota' => 'SENTINEL-GLOBAL-A',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'SENTINEL-GLOBAL-B',
            'nama_anggota' => 'SENTINEL-GLOBAL-B',
        ]);

        $export = new AnggotaExport([], OrganizationVisibility::global());
        $names = $export->query()->pluck('name')->all();

        $this->assertContains('SENTINEL-GLOBAL-A', $names);
        $this->assertContains('SENTINEL-GLOBAL-B', $names);
    }

    public function test_unsupported_scope_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OrganizationVisibility::organization('');
    }

    public function test_full_pii_export_requires_dedicated_permission(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $this->actingAs($admin)
            ->get(route('cooperative.members.export', ['include_pii' => 1, 'reason' => 'Audit']))
            ->assertForbidden();
    }

    public function test_full_pii_export_requires_reason_and_is_audited(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.export', ['include_pii' => 1]))
            ->assertSessionHasErrors('reason');

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.export', [
                'include_pii' => 1,
                'reason_code' => 'business_verification',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.pii.exported',
            'reason' => null,
            'new_values->reason_code' => 'business_verification',
            'new_values->reason_supplied' => true,
        ]);
    }

    public function test_legacy_free_text_export_reason_is_not_persisted(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');
        $sentinel = 'sensitive reason sentinel 3201234567890001';

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.export', [
                'include_pii' => 1,
                'reason' => $sentinel,
            ]))
            ->assertOk();

        $auditContents = DB::table('audit_logs')
            ->where('action', 'member.pii.exported')
            ->get()
            ->map(fn (object $audit): string => json_encode((array) $audit, JSON_THROW_ON_ERROR))
            ->implode(' ');
        $audit = DB::table('audit_logs')
            ->where('action', 'member.pii.exported')
            ->latest('id')
            ->first();
        $newValues = json_decode((string) $audit->new_values, true, 512, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($sentinel, $auditContents);
        $this->assertSame('other', $newValues['reason_code']);
        $this->assertTrue($newValues['reason_supplied']);
    }

    public function test_pii_viewer_can_exact_search_formatted_and_unformatted_nik_and_npwp(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'identity_number' => '3201-2345 6789 0001',
            'npwp' => '12.345.678.9-012.000',
        ]);

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.index', ['search' => '3201-2345 6789 0001']))
            ->assertInertia(fn ($page) => $page->has('members.data', 1));

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.index', ['search' => '3201234567890001']))
            ->assertInertia(fn ($page) => $page->has('members.data', 1));

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.index', ['search' => '12.345.678.9-012.000']))
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    }

    public function test_non_pii_viewer_cannot_discover_members_by_nik_or_npwp(): void
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->assignRole('Kasir Koperasi');
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'identity_number' => '3201234567890001',
            'npwp' => '123456789012000',
        ]);

        $this->actingAs($cashier)
            ->get(route('cooperative.members.index', ['search' => '3201234567890001']))
            ->assertInertia(fn ($page) => $page->has('members.data', 0));

        $this->actingAs($cashier)
            ->get(route('cooperative.members.index', ['search' => '123456789012000']))
            ->assertInertia(fn ($page) => $page->has('members.data', 0));
    }

    public function test_organization_scope_hides_sensitive_search_existence(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $viewer = User::factory()->create(['organization_id' => $orgA->id]);
        $viewer->assignRole('Admin Koperasi');
        $viewer->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'identity_number' => '3201234567890001',
        ]);

        $this->actingAs($viewer)
            ->get(route('cooperative.members.index', ['search' => '3201234567890001']))
            ->assertInertia(fn ($page) => $page->has('members.data', 0));
    }

    public function test_export_audit_does_not_store_raw_sensitive_search_value(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Pengurus Koperasi');
        $sentinel = '3201234567890001';
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'identity_number' => $sentinel,
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.members.export', ['search' => $sentinel]))
            ->assertOk();

        $auditContents = DB::table('audit_logs')->pluck('new_values')->implode(' ');
        $this->assertStringNotContainsString($sentinel, $auditContents);
        $this->assertStringContainsString('search_mode', $auditContents);
    }
}
