<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
        {--password= : Test-only password input; rejected outside APP_ENV=testing}
        {--password-stdin : Read the password from standard input for non-interactive bootstrap}
        {--role=System Admin : Privileged administrative role to assign}
        {--update-existing : Explicitly allow modifying an existing user account}';

    protected $description = 'Create a new administrative user or update existing with explicit confirmation. Safe for production bootstrap.';

    public function handle(): int
    {
        $email = trim((string) ($this->option('email') ?: $this->ask('Admin email address')));
        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);
        if ($emailValidator->fails()) {
            $this->error('A valid email address is required.');

            return self::FAILURE;
        }

        if ($this->option('password') !== null && ! app()->environment('testing')) {
            $this->error('The --password option is available only in automated tests. Use hidden interactive input or --password-stdin.');

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

        $existingUser = User::where('email', $email)->first();
        $isUpdate = (bool) $this->option('update-existing');

        if ($existingUser && ! $isUpdate) {
            $this->error("User with email '{$email}' already exists. Use --update-existing to explicitly modify an existing user.");

            return self::FAILURE;
        }

        $providedPassword = $this->readPassword($existingUser !== null);
        if ($providedPassword === false) {
            return self::FAILURE;
        }
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
                $this->error('A password is required to create an administrator.');

                return self::FAILURE;
            }

            $headOffice = Organization::query()->where('code', 'KOP-001')->first();
            $organizationId = $headOffice?->id;

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($passwordToSet),
                'organization_id' => $organizationId,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $user->assignRole($roleName);

            $this->info("Admin user '{$name}' created successfully with role '{$roleName}'.");
            $this->line("Email: {$email}");

        }

        return self::SUCCESS;
    }

    private function readPassword(bool $allowMissing): string|false|null
    {
        if ($allowMissing && $this->option('password') === null && ! $this->option('password-stdin')) {
            return null;
        }

        if ($this->option('password') !== null) {
            return (string) $this->option('password');
        }

        if ($this->option('password-stdin')) {
            $password = fgets(STDIN);
            if ($password === false) {
                $this->error('No password was provided on standard input.');

                return false;
            }

            return rtrim($password, "\r\n");
        }

        if ($allowMissing && (! $this->input->isInteractive() || $this->option('no-interaction'))) {
            return null;
        }

        if (! $this->input->isInteractive() || $this->option('no-interaction')) {
            $this->error('Password required: run interactively or provide it through --password-stdin.');

            return false;
        }

        $password = $this->secret('Admin password');
        $confirmation = $this->secret('Confirm admin password');
        if (! is_string($password) || ! hash_equals($password, (string) $confirmation)) {
            $this->error('Password confirmation does not match.');

            return false;
        }

        return $password;
    }
}
