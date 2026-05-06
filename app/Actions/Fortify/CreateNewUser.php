<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private readonly CooperativeHeadOfficeResolver $headOfficeResolver,
        private readonly MemberNumberGenerator $memberNumberGenerator,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'phone' => ['nullable', 'string', 'max:20'],
            'identity_number' => ['nullable', 'string', 'max:40', 'unique:'.CooperativeMember::class.',identity_number'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'identity_number.unique' => 'NIK ini sudah terdaftar di koperasi.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $organization = $this->headOfficeResolver->resolve();

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'organization_id' => $organization->id,
            ]);

            Role::query()->firstOrCreate(['name' => 'Anggota']);
            $user->assignRole('Anggota');

            CooperativeMember::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'member_no' => $this->memberNumberGenerator->generate(),
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'identity_number' => $input['identity_number'] ?? null,
                'address' => $input['address'] ?? null,
                'joined_at' => now()->toDateString(),
                'status' => 'ACTIVE',
            ]);

            return $user;
        });
    }
}
