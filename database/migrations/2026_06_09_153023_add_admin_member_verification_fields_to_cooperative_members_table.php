<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->timestamp('admin_validated_at')->nullable()->after('validation_notes');
            $table->foreignId('admin_validated_by')->nullable()->after('admin_validated_at')
                ->constrained('users')->nullOnDelete();
            $table->text('admin_validation_notes')->nullable()->after('admin_validated_by');
        });

        $this->upsertPermission('verify_cooperative_member');
        $this->upsertPermission('approve_cooperative_member');

        $this->assignPermissionToRole('Admin Koperasi', 'verify_cooperative_member');
        $this->assignPermissionToRole('Pengurus Koperasi', 'approve_cooperative_member');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('cooperative_members', 'admin_validated_by')) {
            Schema::table('cooperative_members', function (Blueprint $table): void {
                $table->dropForeign(['admin_validated_by']);
            });
        }

        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropColumn([
                'admin_validated_at',
                'admin_validated_by',
                'admin_validation_notes',
            ]);
        });
    }

    private function upsertPermission(string $name): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => $name, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    private function assignPermissionToRole(string $roleName, string $permissionName): void
    {
        $roleId = DB::table('roles')
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('name', $permissionName)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId || ! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->updateOrInsert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }
};
