<?php

namespace App\Http\Requests\Cooperative;

use App\Models\CooperativeMember;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCooperativeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $namaAnggota = (string) $this->input('nama_anggota', $this->input('name', ''));
        $reason = $this->input('correction_reason', $this->input('reason'));

        $merge = [
            'nama_anggota' => $namaAnggota,
            'name' => rtrim(rtrim($namaAnggota, '*')),
            'phone' => $this->input('no_telp', $this->input('phone')),
            'jenis_anggota' => str_ends_with(trim($namaAnggota), '*') ? 'ALB' : $this->input('jenis_anggota', 'AB'),
            'jenis_kelamin' => $this->input('jenis_kelamin', 'L'),
            'kategori' => $this->input('kategori', 'KOP'),
        ];

        if ($reason !== null) {
            $merge['reason'] = $reason;
            $merge['correction_reason'] = $reason;
        }

        $this->merge($merge);
    }

    public function targetMember(): ?CooperativeMember
    {
        $member = $this->route('member');
        if ($member instanceof CooperativeMember) {
            return $member;
        }

        if (is_numeric($member) || is_string($member)) {
            return CooperativeMember::query()->find($member);
        }

        return null;
    }

    public function rules(): array
    {
        $member = $this->targetMember();
        $savedJoinedAt = $member?->joined_at?->toDateString();
        $savedTanggalAktif = $member?->tanggal_aktif?->toDateString();

        $safeDate = function (mixed $val): ?string {
            if (! is_string($val) || trim($val) === '') {
                return null;
            }
            try {
                return Carbon::parse($val)->toDateString();
            } catch (\Throwable) {
                return (string) $val;
            }
        };

        $hasJoinedAt = $this->has('joined_at') && $this->input('joined_at') !== null && $this->input('joined_at') !== '';
        $hasTanggalAktif = $this->has('tanggal_aktif') && $this->input('tanggal_aktif') !== null && $this->input('tanggal_aktif') !== '';

        $newJoinedAt = $hasJoinedAt ? $safeDate($this->input('joined_at')) : $savedJoinedAt;
        $newTanggalAktif = $hasTanggalAktif ? $safeDate($this->input('tanggal_aktif')) : $savedTanggalAktif;

        $datesChanged = false;
        if ($this->has('joined_at') && $newJoinedAt !== $savedJoinedAt) {
            $datesChanged = true;
        }
        if ($this->has('tanggal_aktif') && $newTanggalAktif !== $savedTanggalAktif) {
            $datesChanged = true;
        }

        $tanggalAktifRules = ['sometimes', 'required', 'date', 'before_or_equal:today'];
        if ($this->filled('joined_at')) {
            $tanggalAktifRules[] = 'after_or_equal:joined_at';
        } elseif ($savedJoinedAt) {
            $tanggalAktifRules[] = 'after_or_equal:'.$savedJoinedAt;
        }

        $joinedAtRules = ['sometimes', 'required', 'date', 'before_or_equal:today'];
        if ($this->filled('tanggal_aktif')) {
            $joinedAtRules[] = 'before_or_equal:tanggal_aktif';
        } elseif ($savedTanggalAktif) {
            $joinedAtRules[] = 'before_or_equal:'.$savedTanggalAktif;
        }

        $reasonRules = $datesChanged
            ? ['required', 'string', 'min:5', 'max:1000']
            : ['nullable', 'string', 'max:1000'];

        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'no_anggota' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('cooperative_members', 'no_anggota')->ignore($this->route('member')?->id),
            ],
            'nama_anggota' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'jenis_anggota' => ['required', 'in:AB,ALB'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kategori' => ['required', 'in:IP,CDB,KOP'],
            'autodebet' => ['required', 'in:BNI,BRI,MANUAL'],
            'opening_saving_balance' => ['nullable', 'numeric', 'min:0'],
            'joined_at' => $joinedAtRules,
            'tanggal_aktif' => $tanggalAktifRules,
            'reason' => $reasonRules,
            'correction_reason' => $reasonRules,
            'user_id' => ['prohibited'],
            'member_no' => ['prohibited'],
            'organization_id' => ['prohibited'],
            'status' => ['prohibited'],
            'validation_status' => ['prohibited'],
            'resigned_at' => ['prohibited'],
            'validated_at' => ['prohibited'],
            'validated_by' => ['prohibited'],
            'validation_notes' => ['prohibited'],
            'admin_validated_at' => ['prohibited'],
            'admin_validated_by' => ['prohibited'],
            'admin_validation_notes' => ['prohibited'],
            'profile_completed_at' => ['prohibited'],
            'onboarding_submitted_at' => ['prohibited'],
            'member_login_password' => ['prohibited'],
            'identity_number' => ['prohibited'],
            'npwp' => ['prohibited'],
            'no_rekening' => ['prohibited'],
            'nama_bank' => ['prohibited'],
            'nama_pemilik_rekening' => ['prohibited'],
            'address' => ['prohibited'],
            'notes' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'joined_at.before_or_equal' => 'Tanggal bergabung tidak boleh di masa depan.',
            'joined_at.date' => 'Tanggal bergabung harus berupa tanggal yang valid.',
            'tanggal_aktif.before_or_equal' => 'Tanggal aktif tidak boleh di masa depan.',
            'tanggal_aktif.after_or_equal' => 'Tanggal aktif tidak boleh lebih awal dari tanggal bergabung.',
            'tanggal_aktif.date' => 'Tanggal aktif harus berupa tanggal yang valid.',
            'correction_reason.required' => 'Alasan koreksi tanggal keanggotaan wajib diisi.',
            'reason.required' => 'Alasan koreksi tanggal keanggotaan wajib diisi.',
            'correction_reason.min' => 'Alasan koreksi minimal 5 karakter.',
            'reason.min' => 'Alasan koreksi minimal 5 karakter.',
        ];
    }
}
