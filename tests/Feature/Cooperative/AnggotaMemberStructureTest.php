<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnggotaMemberStructureTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('System Admin');
    }

    public function test_cooperative_member_store_uses_anggota_fields(): void
    {
        $this->actingAs($this->admin)->post(route('cooperative.members.store'), [
            'no_anggota' => '003',
            'tanggal_aktif' => '2015-01-01',
            'nama_anggota' => 'Budi Santoso*',
            'no_telp' => '081234560003',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'no_rekening' => 'MANUAL',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('no_anggota', '003')->firstOrFail();

        $this->assertSame('003', $member->member_no);
        $this->assertSame('Budi Santoso', $member->name);
        $this->assertSame('Budi Santoso', $member->nama_anggota_clean);
        $this->assertSame('ALB', $member->jenis_anggota);
        $this->assertSame('081234560003', $member->phone);
        $this->assertNull($member->no_rekening);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->validation_status);
    }

    public function test_cooperative_members_index_filters_and_exports_anggota(): void
    {
        $organization = Organization::factory()->create();
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'no_anggota' => '001',
            'nama_anggota' => 'Siti Aminah',
            'member_no' => '001',
            'name' => 'Siti Aminah',
            'status' => 'ACTIVE',
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'no_anggota' => '002',
            'nama_anggota' => 'Ratna Sari*',
            'member_no' => '002',
            'name' => 'Ratna Sari',
            'status' => 'RESIGNED',
            'jenis_anggota' => 'ALB',
            'kategori' => 'KOP',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', [
                'status' => 'INACTIVE',
                'jenis_anggota' => 'ALB',
                'kategori' => 'KOP',
            ]))
            ->assertOk();

        $response = $this->actingAs($this->admin)
            ->get(route('cooperative.members.export', ['status' => 'INACTIVE']))
            ->assertOk();

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);
        $this->assertFileExists($file->getPathname());
        @unlink($file->getPathname());
    }

    public function test_cooperative_member_destroy_soft_deletes_member(): void
    {
        $member = CooperativeMember::factory()->create([
            'no_anggota' => '009',
            'member_no' => '009',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('cooperative.members.destroy', $member))
            ->assertRedirect(route('cooperative.members.index'));

        $this->assertSoftDeleted('cooperative_members', ['id' => $member->id]);
    }

    public function test_cooperative_members_index_includes_organization_name(): void
    {
        $organization = Organization::factory()->create(['name' => 'Koperasi Jaya Bersama']);
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'no_anggota' => '010',
            'member_no' => '010',
            'nama_anggota' => 'Agus Setiawan',
            'name' => 'Agus Setiawan',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Index')
                ->where('members.data.0.organization_id', $organization->id)
                ->where('members.data.0.organization_name', 'Koperasi Jaya Bersama')
            );
    }

    public function test_member_summary_includes_revision_and_is_organization_scoped(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        CooperativeMember::factory()->active()->count(5)->create([
            'organization_id' => $organization->id,
        ]);
        CooperativeMember::factory()->pending()->create([
            'organization_id' => $organization->id,
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
        ]);
        CooperativeMember::factory()->active()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Index')
                ->loadDeferredProps('member-stats', fn (Assert $page) => $page
                    ->where('stats.total', 7)
                    ->where('stats.active', 5)
                    ->where('stats.pending_validation', 1)
                    ->where('stats.revision', 1)
                )
            );
    }

    public function test_cooperative_member_show_includes_savings_summary_by_category(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'no_anggota' => '011',
            'member_no' => '011',
            'nama_anggota' => 'Dewi Lestari',
            'name' => 'Dewi Lestari',
            'status' => 'ACTIVE',
            'tanggal_aktif' => '2026-05-01',
            'joined_at' => '2026-04-20',
            'jenis_kelamin' => 'P',
            'kategori' => 'KOP',
            'autodebet' => 'BNI',
        ]);
        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);
        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'entry_type' => 'OPENING_BALANCE',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'POKOK',
            'debit' => 0,
            'credit' => 200000,
            'posted_at' => '2026-05-01',
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajib->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'posted_at' => '2026-05-10',
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_WITHDRAWAL',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'debit' => 10000,
            'credit' => 25000,
            'posted_at' => '2026-05-15',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cooperative.members.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Show')
                ->where('member.id', $member->id)
                ->where('member.tanggal_aktif', '2026-05-01T00:00:00.000000Z')
                ->where('member.joined_at', '2026-04-20T00:00:00.000000Z')
                ->where('member.jenis_kelamin', 'P')
                ->where('member.kategori', 'KOP')
                ->where('member.autodebet', 'BNI')
                ->where('savingsSummary.total_balance', 265000)
                ->where('savingsSummary.by_category.POKOK', 200000)
                ->where('savingsSummary.by_category.WAJIB', 50000)
                ->where('savingsSummary.by_category.SUKARELA', 15000)
                ->has('recentSavingsEntries', 3)
            );
    }
}
