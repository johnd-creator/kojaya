<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdminUserCommand extends Command
{
    /**
     * Permitted administrative roles for privileged bootstrap.
     *
     * @var list<string>
     */
    public const ALLOWED_ADMIN_ROLES = [
        'System Admin',
        'Admin Pusat',
        'Pengurus Koperasi',
        'Manajer Koperasi',
        'Admin Koperasi',
    ];

    protected $signature = 'admin:create
        {--email= : Admin email address}
        {--name= : Admin display name}
        {--password= : Admin password (optional; omitted generates a secure random password). WARNING: Passing passwords via CLI option may expose them in shell history and process lists.}
        {--role=System Admin : Privileged administrative role to assign}
        {--update-existing : Explicitly allow modifying an existing user account}';

    protected $description = 'Create a new administrative user or update existing with explicit confirmation. Safe for production bootstrap.';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Admin email address');
        if (empty($email)) {
            $this->error('Email address is required.');

            return self::FAILURE;
        }

        $roleName = (string) $this->option('role');
        if (! in_array($roleName, self::ALLOWED_ADMIN_ROLES, true)) {
            $this->error("Role '{$roleName}' is not an authorized administrative role. Allowed: ".implode(', ', self::ALLOWED_ADMIN_ROLES));

            return self::FAILURE;
        }

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("Role '{$roleName}' does not exist in the database. Run RolePermissionSeeder first.");

            return self::FAILURE;
        }

        $providedPassword = $this->option('password');
        if ($providedPassword !== null) {
            $validator = Validator::make(
                ['password' => $providedPassword],
                [
                    'password' => [
                        'required',
                        'string',
                        Password::min(12)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols(),
                    ],
                ],
                [
                    'password.required' => 'The password field cannot be empty.',
                ],
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }
        }

        $existingUser = User::where('email', $email)->first();
        $isUpdate = (bool) $this->option('update-existing');

        if ($existingUser && ! $isUpdate) {
            $this->error("User with email '{$email}' already exists. Use --update-existing to explicitly modify an existing user.");

            return self::FAILURE;
        }

        $generatedPassword = null;

        if ($existingUser) {
            $name = $this->option('name') ?: $existingUser->name;

            $updateData = [
                'name' => $name,
            ];

            if ($providedPassword !== null) {
                $updateData['password'] = Hash::make($providedPassword);
            }

            $existingUser->update($updateData);
            $user = $existingUser;

            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            $this->info("User '{$user->name}' ({$email}) updated successfully.");
            if ($providedPassword !== null) {
                $this->info('Password was updated.');
            } else {
                $this->info('Existing password was preserved.');
            }
        } else {
            $name = $this->option('name') ?: 'System Admin';
            $passwordToSet = $providedPassword;

            if ($passwordToSet === null) {
                $generatedPassword = Str::password(24);
                $passwordToSet = $generatedPassword;
            }

            $headOffice = Organization::query()->where('code', 'KOP-001')->first();
            $organizationId = $headOffice?->id;

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($passwordToSet),
                'organization_id' => $organizationId,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($roleName);

            $this->info("Admin user '{$name}' created successfully with role '{$roleName}'.");
            $this->line("Email: {$email}");

            if ($generatedPassword !== null) {
                $this->warn('Generated secure password (save this now, it will not be shown again):');
                $this->line($generatedPassword);
            }
        }

        return self::SUCCESS;
    }
}
