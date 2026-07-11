<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'admin:create
        {--email= : Admin email address}
        {--name= : Admin display name}
        {--password= : Admin password (random if omitted)}
        {--role=System Admin : Role to assign}';

    protected $description = 'Create or update a production admin user with a secure password. Use this instead of the seeder for production deployments.';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Admin email address');
        $name = $this->option('name') ?? $this->ask('Admin display name', 'System Admin');
        $password = $this->option('password') ?? Str::password(24);
        $roleName = $this->option('role');

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("Role '{$roleName}' does not exist. Run the RolePermissionSeeder first.");

            return self::FAILURE;
        }

        $organization = Organization::query()->where('code', 'KOP-001')->first();
        $organizationId = $organization?->id;

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'organization_id' => $organizationId,
            ]
        );

        if (! $user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        $this->info("Admin user '{$name}' created/updated successfully.");
        $this->line("Email: {$email}");

        if (! $this->option('password')) {
            $this->warn('Generated password (save this now, it will not be shown again):');
            $this->line($password);
        }

        return self::SUCCESS;
    }
}
