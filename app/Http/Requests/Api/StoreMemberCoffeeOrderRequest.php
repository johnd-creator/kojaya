<?php

namespace App\Http\Requests\Api;

use App\Models\CooperativeMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberCoffeeOrderRequest extends FormRequest
{
    private ?CooperativeMember $activeMember = null;

    private bool $activeMemberResolved = false;

    public function activeMember(): ?CooperativeMember
    {
        if (! $this->activeMemberResolved) {
            $this->activeMember = $this->user()?->cooperativeMember()->active()->first();
            $this->activeMemberResolved = true;
        }

        return $this->activeMember;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $member = $this->activeMember();
        if ($member === null) {
            return false;
        }

        if (empty($member->organization_id)) {
            return false;
        }

        return true;
    }

    protected function failedAuthorization(): void
    {
        $member = $this->activeMember();
        if ($member === null) {
            throw new AuthorizationException('Akun belum terhubung dengan anggota koperasi aktif.');
        }

        throw new AuthorizationException('Organisasi koperasi tidak ditemukan.');
    }

    public function memberOrganizationId(): ?string
    {
        $member = $this->activeMember();

        return $member?->organization_id ? (string) $member->organization_id : null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->memberOrganizationId();

        $productExistsRule = Rule::exists('pos_products', 'id')->where(function ($query) use ($orgId): void {
            if ($orgId !== null) {
                $query->where('organization_id', $orgId);
            } else {
                $query->whereRaw('1 = 0');
            }
        });

        return [
            'pos_product_id' => ['required_without:items', $productExistsRule],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:12'],
            'items' => ['nullable', 'array', 'min:1', 'max:12'],
            'items.*.pos_product_id' => ['required_with:items', $productExistsRule],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:12'],
            'items.*.sugar_level' => ['nullable', 'in:Normal,Less Sugar,No Sugar'],
            'items.*.ice_level' => ['nullable', 'in:Normal,Less Ice,Warm'],
            'items.*.cup_size' => ['nullable', 'in:Reguler,Large'],
            'client_reference' => ['nullable', 'string', 'max:80'],
            'channel' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'payment_method' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'sugar_level' => ['nullable', 'in:Normal,Less Sugar,No Sugar'],
            'ice_level' => ['nullable', 'in:Normal,Less Ice,Warm'],
            'cup_size' => ['nullable', 'in:Reguler,Large'],
        ];
    }

    public function messages(): array
    {
        return [
            'pos_product_id.required_without' => 'Menu kopi wajib dipilih.',
            'pos_product_id.exists' => 'Menu kopi tidak tersedia.',
            'items.min' => 'Keranjang kopi minimal berisi satu item.',
            'items.*.pos_product_id.required_with' => 'Menu kopi wajib dipilih.',
            'items.*.pos_product_id.exists' => 'Menu kopi tidak tersedia.',
            'quantity.max' => 'Maksimal 12 cup per pesanan.',
            'items.*.quantity.max' => 'Maksimal 12 cup per item.',
        ];
    }
}
