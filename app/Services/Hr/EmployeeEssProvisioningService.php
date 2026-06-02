<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EmployeeEssProvisioningService
{
    /**
     * Create an ESS user account for an employee using a randomly-generated
     * password. Returns the password reset link so the operator/UI can deliver
     * it to the employee through a secure channel (email, in-person handover,
     * etc.).
     *
     * @return array{user: User, reset_link: string|null, password_status: string}
     */
    public function enable(Employee $employee): array
    {
        if ($employee->user_id) {
            throw ValidationException::withMessages([
                'employee' => 'Karyawan ini sudah memiliki akun ESS.',
            ]);
        }

        if (! $employee->email) {
            throw ValidationException::withMessages([
                'employee' => 'Karyawan harus memiliki alamat email sebelum mengaktifkan ESS.',
            ]);
        }

        if (User::query()->where('email', $employee->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'User dengan email ini sudah ada. Silakan tautkan secara manual.',
            ]);
        }

        return DB::transaction(function () use ($employee): array {
            // Generate a strong random password the operator never sees.
            $randomPassword = Str::password(20, true, true, true, false);

            $user = User::query()->create([
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'email' => $employee->email,
                'password' => Hash::make($randomPassword),
                'organization_id' => $employee->organization_id,
            ]);

            Role::query()->firstOrCreate(['name' => 'Employee']);
            $user->assignRole('Employee');

            $employee->forceFill(['user_id' => $user->id])->save();

            $resetLink = $this->buildResetLink($user);

            return [
                'user' => $user,
                'reset_link' => $resetLink,
                'password_status' => 'reset_required',
            ];
        });
    }

    public function disable(Employee $employee): void
    {
        if (! $employee->user_id) {
            throw ValidationException::withMessages([
                'employee' => 'Tidak ada akun ESS yang ditautkan ke karyawan ini.',
            ]);
        }

        DB::transaction(function () use ($employee): void {
            $employee->forceFill(['user_id' => null])->save();
        });
    }

    /**
     * Send a password reset link via the configured broker. Falls back to
     * generating a token directly if the mail driver is `array`/`log`, so the
     * operator can copy the link from the response when running in non-mail
     * environments.
     */
    private function buildResetLink(User $user): ?string
    {
        try {
            $broker = Password::broker();
            $token = $broker->createToken($user);

            return route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);
        } catch (\Throwable) {
            // If the broker/route is misconfigured, do not break provisioning;
            // the operator can trigger forgot-password from the login screen.
            return null;
        }
    }
}
