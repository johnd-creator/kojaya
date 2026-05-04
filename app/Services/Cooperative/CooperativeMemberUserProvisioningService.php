<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CooperativeMemberUserProvisioningService
{
    public function __construct(
        private readonly CooperativeHeadOfficeResolver $headOfficeResolver,
    ) {}

    public function provision(CooperativeMember $member, ?string $plainPassword = null): ?User
    {
        if (! $member->email && ! $member->user_id) {
            return null;
        }

        $user = $member->user;

        if (! $user && $member->email) {
            $user = User::query()->where('email', $member->email)->first();
        }

        if (! $user && $member->email) {
            $user = User::query()->create([
                'name' => $member->name,
                'email' => $member->email,
                'password' => Hash::make($plainPassword ?: Str::password(16)),
                'organization_id' => $this->headOfficeResolver->resolve()->id,
            ]);
        }

        if (! $user) {
            return null;
        }

        if ($user->cooperativeMember()->whereKeyNot($member->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'User ini sudah tertaut dengan anggota koperasi lain.',
            ]);
        }

        Role::query()->firstOrCreate(['name' => 'Anggota']);

        if (! $user->hasRole('Anggota')) {
            $user->assignRole('Anggota');
        }

        if ((int) $member->user_id !== (int) $user->id) {
            $member->forceFill(['user_id' => $user->id])->save();
        }

        return $user;
    }
}
