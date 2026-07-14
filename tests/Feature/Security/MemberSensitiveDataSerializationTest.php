<?php

namespace Tests\Feature\Security;

use App\Enums\PermissionEnum;
use App\Http\Resources\CooperativeMemberResource;
use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MemberSensitiveDataSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_member_serialization_excludes_plaintext_and_crypto_metadata(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
            'npwp' => '123456789012000',
            'no_rekening' => 'AB-123456',
        ]);

        $serialized = $member->toArray();
        $json = $member->toJson();

        foreach ([
            'identity_number',
            'npwp',
            'no_rekening',
            'identity_number_enc',
            'identity_number_bidx',
            'identity_number_key_version',
            'identity_number_bidx_version',
            'identity_number_migrated_at',
            'npwp_enc',
            'npwp_bidx',
            'npwp_key_version',
            'npwp_bidx_version',
            'npwp_migrated_at',
            'no_rekening_enc',
            'no_rekening_bidx',
            'no_rekening_key_version',
            'no_rekening_bidx_version',
            'no_rekening_migrated_at',
        ] as $field) {
            $this->assertArrayNotHasKey($field, $serialized);
            $this->assertStringNotContainsString($field, $json);
        }
    }

    public function test_explicit_resource_respects_pii_permission_while_generic_serialization_stays_hidden(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
            'npwp' => '123456789012000',
            'no_rekening' => '1234567890',
        ]);
        $user = User::factory()->create();
        $request = Request::create('/api/v1/members/'.$member->id);
        $request->setUserResolver(fn (): User => $user);

        $masked = (new CooperativeMemberResource($member))->toArray($request);
        $this->assertNotSame('3201234567890001', $masked['identity_number']);
        $this->assertNotSame('123456789012000', $masked['npwp']);
        $this->assertNotSame('1234567890', $masked['no_rekening']);

        Permission::firstOrCreate(['name' => PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value);

        $unmasked = (new CooperativeMemberResource($member))->toArray($request);
        $this->assertSame('3201234567890001', $unmasked['identity_number']);
        $this->assertSame('123456789012000', $unmasked['npwp']);
        $this->assertSame('1234567890', $unmasked['no_rekening']);
    }
}
